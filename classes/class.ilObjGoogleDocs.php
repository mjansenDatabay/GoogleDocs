<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPlugin.php';
require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';

/**
 *
 */
class ilObjGoogleDocs extends ilObjectPlugin implements ilGoogleDocsConstants
{
	/**
	 * @var int
	 */
	private $doc_type = self::GOOGLE_DOC;

	/**
	 * @var string
	 */
	private $doc_url = '';

	/**
	 * @var string
	 */
	private $edit_doc_url = '';

	/**
	 * @var array
	 */
	private $local_roles = array();

	/**
	 * @param int $ref_id
	 */
	public function __construct($ref_id = 0)
	{
		parent::__construct($ref_id);
		$this->plugin->includeClass('class.ilGoogleDocsAPI.php');
	}

	/**
	 * @return array|void
	 */
	public function initDefaultRoles()
	{
		/**
		 * @var $rbacadmin  ilRbacAdmin
		 * @var $rbacreview ilRbacReview
		 * @var $ilDB       ilDB
		 */
		global $rbacadmin, $rbacreview, $ilDB;

		$rolf_obj = $this->createRoleFolder();

		/**
		 * @var $role_obj ilObjRole
		 */
		$role_obj = $rolf_obj->createRole('il_xgdo_reader_' . $this->getRefId(), 'Reader of google docs object obj_no.' . $this->getId());
		$query    = "SELECT obj_id FROM object_data WHERE type = %s AND title = %s";

		$row = $ilDB->fetchAssoc(
			$ilDB->queryF(
				$query,
				array('text', 'text'),
				array('rolt', 'il_xgdo_reader')
			)
		);
		$rbacadmin->copyRoleTemplatePermissions($row['obj_id'], ROLE_FOLDER_ID, $rolf_obj->getRefId(), $role_obj->getId());
		$ops = $rbacreview->getOperationsOfRole($role_obj->getId(), 'xgdo', $rolf_obj->getRefId());
		$rbacadmin->grantPermission($role_obj->getId(), $ops, $this->getRefId());


		$role_obj = $rolf_obj->createRole('il_xgdo_writer_' . $this->getRefId(), 'Writer of google docs object obj_no.' . $this->getId());
		$query    = "SELECT obj_id FROM object_data WHERE type = %s AND title = %s";

		$row = $ilDB->fetchAssoc(
			$ilDB->queryF(
				$query,
				array('text', 'text'),
				array('rolt', 'il_xgdo_writer')
			)
		);
		$rbacadmin->copyRoleTemplatePermissions($row['obj_id'], ROLE_FOLDER_ID, $rolf_obj->getRefId(), $role_obj->getId());
		$ops = $rbacreview->getOperationsOfRole($role_obj->getId(), 'xgdo', $rolf_obj->getRefId());
		$rbacadmin->grantPermission($role_obj->getId(), $ops, $this->getRefId());

		parent::initDefaultRoles();
	}

	/**
	 * @return int
	 */
	public function getDefaultWriterRole()
	{
		$local_roles = $this->getLocalRoles();
		if(isset($local_roles['il_xgdo_writer_' . $this->getRefId()]))
		{
			return $local_roles['il_xgdo_writer_' . $this->getRefId()];
		}

		return 0;
	}

	/**
	 * @return int
	 */
	public function getDefaultReaderRole()
	{
		$local_roles = $this->getLocalRoles();
		if(isset($local_roles['il_xgdo_reader_' . $this->getRefId()]))
		{
			return $local_roles['il_xgdo_reader_' . $this->getRefId()];
		}

		return 0;
	}

	/**
	 * @param bool $a_translate
	 * @return array
	 */
	protected function getLocalRoles($a_translate = false)
	{
		/**
		 * @var $rbacreview ilRbacReview
		 */
		global $rbacreview;

		if(!$this->local_roles)
		{
			$this->local_roles = array();
			$rolf              = $rbacreview->getRoleFolderOfObject($this->getRefId());
			$role_arr          = $rbacreview->getRolesOfRoleFolder($rolf['ref_id']);

			foreach($role_arr as $role_id)
			{
				if($rbacreview->isAssignable($role_id, $rolf['ref_id']) == true)
				{
					$this->local_roles[ilObject::_lookupTitle($role_id)] = $role_id;
				}
			}
		}

		return $this->local_roles;
	}

	/**
	 *
	 */
	protected function initType()
	{
		$this->setType('xgdo');
	}

	/**
	 * @param int $a_doc_type
	 */
	public function setDocType($a_doc_type)
	{
		$this->doc_type = $a_doc_type;
	}

	/**
	 * @return int
	 */
	public function getDocType()
	{
		return $this->doc_type;
	}

	/**
	 * @param string $a_doc_url
	 */
	public function setDocUrl($a_doc_url)
	{
		$this->doc_url = $a_doc_url;
	}

	/**
	 * @return string
	 */
	public function getDocUrl()
	{
		return $this->doc_url;
	}

	/**
	 * @param $a_edit_doc_url
	 */
	public function setEditDocUrl($a_edit_doc_url)
	{
		$this->edit_doc_url = $a_edit_doc_url;
	}

	/**
	 * @return string
	 */
	public function getEditDocUrl()
	{
		return $this->edit_doc_url;
	}

	/**
	 * @return bool|void
	 * @throws ilException
	 */
	protected function beforeCreate()
	{
		if(!isset($_POST['doc_type']) || !in_array((int)$_POST['doc_type'], array(
			self::GOOGLE_DOC,
			self::GOOGLE_XLS,
			self::GOOGLE_PPT
		))
		)
		{
			throw new ilException(self::CREATION_ERROR_TYPE_MISMATCH);
		}

		return true;
	}

	/**
	 * @throws ilException
	 */
	public function doCreate()
	{
		/**
		 * @var $ilDB   ilDB
		 * @var $ilLog  ilLog
		 * @var $ilUser ilObjUser
		 */
		global $ilDB, $ilLog, $ilUser;

		$this->setDocType((int)$_POST['doc_type']);

		try
		{
			$api          = ilGoogleDocsAPI::getInstance();
			$doc_id       = $api->createDocumentByType($this->getTitle(), $this->getDocType());
			$document     = $api->docs->getDocumentListEntry($doc_id->getText());
			$edit_doc_url = '';
			foreach($document->getLink() as $link)
			{
				/**
				 * @var $link Zend_Gdata_App_Extension_link
				 */

				if($link->getRel() == 'alternate')
				{
					$edit_doc_url = $link->getHref();
				}
			}
			$this->setDocUrl($doc_id);
			$this->setEditDocUrl($edit_doc_url);

			$ilDB->insert(
				'rep_robj_xgdo_data',
				array(
					'obj_id'       => array('integer', $this->getId()),
					'doc_type'     => array('integer', $this->getDocType()),
					'doc_url'      => array('text', $this->getDocUrl()),
					'edit_doc_url' => array('text', $this->getEditDocUrl())
				)
			);
		}
		catch(Exception $e)
		{
			$ilLog->write($e->getMessage());
			$ilLog->logStack();

			$this->delete();

			throw new ilException(self::CREATION_ERROR_INCOMPLETE);
		}
	}

	/**
	 * Read data from db
	 */
	public function doRead()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$res = $ilDB->query('SELECT * FROM rep_robj_xgdo_data WHERE obj_id = ' . $ilDB->quote($this->getId(), 'integer'));
		while($row = $ilDB->fetchAssoc($res))
		{
			$this->setDocType($row['doc_type']);
			$this->setDocUrl($row['doc_url']);
			$this->setEditDocUrl($row['edit_doc_url']);
		}
	}

	/**
	 * Update data
	 */
	public function doUpdate()
	{
	}

	/**
	 * @return bool
	 */
	public function beforeDelete()
	{
		// @todo: If we add error handling here in future please have a look at doCreate(), where we call delete() of the base class. Prevent endless loops.
		return true;
	}

	/**
	 * Delete data from database
	 */
	public function doDelete()
	{
		/**
		 * @var $ilDB  ilDB
		 * @var $ilLog ilLog
		 */
		global $ilDB, $ilLog;

		try
		{
			$api = ilGoogleDocsAPI::getInstance();
			$api->deleteDocumentByUrl($this->getDocUrl());
		}
		catch(Exception $e)
		{
			//@todo: Handle Exception
			$ilLog->write($e->getMessage());
			$ilLog->logStack();
		}

		$ilDB->manipulate('DELETE FROM rep_robj_xgdo_data WHERE obj_id = ' . $ilDB->quote($this->getId(), 'integer'));
		$ilDB->manipulate('DELETE FROM rep_robj_xgdo_members WHERE obj_id = ' . $ilDB->quote($this->getId(), 'integer'));

		include_once 'Services/Export/classes/class.ilExport.php';
		include_once 'Services/Export/classes/class.ilExportFileInfo.php';
		ilUtil::delDir(dirname(ilExport::_getExportDirectory($this->getId(), '', $this->getType())));
		ilExportFileInfo::deleteByObjId($this->getId());
	}

	/**
	 * @param ilObjGoogleDocs $new_obj
	 * @param int             $a_target_id
	 * @param int             $a_copy_id
	 * @throws ilException
	 */
	public function doCloneObject($new_obj, $a_target_id, $a_copy_id)
	{
		throw new ilException('Cloning is currently not implemented');
	}

	/**
	 * @param string $type
	 * @return Zend_Http_Response
	 * @throws InvalidArgumentException
	 */
	public function getExportData($type)
	{
		$api = ilGoogleDocsAPI::getInstance();

		switch($this->getDocType())
		{
			case self::GOOGLE_DOC:
				$document = $api->getDocs()->getDocumentListEntry($this->getDocUrl());
				$id       = preg_replace('/(.*document%3A)/', '', $document->getId());
				$response = $api->getDocs()->get("https://docs.google.com/feeds/download/documents/Export?docID={$id}&exportFormat={$type}&format={$type}");
				break;

			// @todo: Implement export for spreadsheets
			/*case self::GOOGLE_XLS:
				break;*/

			case self::GOOGLE_PPT:
				$document = $api->getDocs()->getDocumentListEntry($this->getDocUrl());
				$id       = preg_replace('/(.*presentation%3A)/', '', $document->getId());
				$response = $api->getDocs()->get("https://docs.google.com/feeds/download/presentations/Export?docID={$id}&exportFormat={$type}");
				break;

			default:
				throw new InvalidArgumentException("Document type {$this->getDocType()} not supported");
				break;
		}

		return $response;
	}

	/**
	 * @param ilGoogleDocsParticipant $participant
	 * @return bool
	 */
	public function hasToSubmitGoogleAccount(ilGoogleDocsParticipant $participant)
	{
		return $participant->isAssigned() && !$participant->hasGoogleAccount();
	}

	/**
	 * @param ilGoogleDocsParticipant $participant
	 */
	public function grantAclPermissions(ilGoogleDocsParticipant $participant)
	{
		$api        = ilGoogleDocsAPI::getInstance();

		$document   = $api->getDocs()->getDocumentListEntry((string)$this->getDocUrl());
		$feed       = $api->getDocs()->getAclFeed($document);
		$role_value = $this->extractAclRoleValue($feed, $participant);

		switch(true)
		{
			case $participant->isWriter() && !strlen($role_value):
				$this->addAclEntry($participant, $document, self::GDOC_WRITER);
				break;

			case $participant->isWriter() && !in_array($role_value, array('owner', 'writer')):
				$this->updateAclEntry($participant, $document, self::GDOC_WRITER);
				break;

			case $participant->isReader() && !strlen($role_value):
				$this->addAclEntry($participant, $document, self::GDOC_READER);
				break;

			case $participant->isReader() && !in_array($role_value, array('reader')):
				$this->updateAclEntry($participant, $document, self::GDOC_READER);
				break;
		}
	}

	/**
	 * @param Zend_Gdata_App_Feed     $feed
	 * @param ilGoogleDocsParticipant $participant
	 * @return string
	 */
	protected function extractAclRoleValue(Zend_Gdata_App_Feed $feed, ilGoogleDocsParticipant $participant)
	{
		$role = null;
		foreach($feed->getEntry() as $entry)
		{
			/**
			 * @var $entry Zend_Gdata_Docs_AclEntry
			 */
			$scope = $entry->getAclScope();
			if('user' == $scope->getType() &&
				(
					$participant->getGoogleAccount() == $scope->getValue() ||
					str_replace('@gmail.com', '@googlemail.com', $participant->getGoogleAccount()) == $scope->getValue()
				)
			)
			{
				$role = $entry->getAclRole();
				break;
			}
		}

		if($role instanceof Zend_Gdata_Acl_Role)
		{
			return $role->getValue();
		}

		return '';
	}

	/**
	 * @param ilGoogleDocsParticipant           $participant
	 * @param Zend_Gdata_Docs_DocumentListEntry $document
	 * @param int                               $type
	 * @throws InvalidArgumentException
	 */
	public function addAclEntry(ilGoogleDocsParticipant $participant, Zend_Gdata_Docs_DocumentListEntry $document, $type)
	{
		$api = ilGoogleDocsAPI::getInstance();

		$role = new Zend_Gdata_Acl_Role();
		switch($type)
		{
			case self::GDOC_WRITER:
				$role->setValue('writer');
				break;

			case self::GDOC_READER:
				$role->setValue('reader');
				break;

			default:
				throw new InvalidArgumentException("Role {$type} is currently not supported for the google docs acl list");
				break;
		}

		$scope = new Zend_Gdata_Acl_Scope();
		$scope->setValue($participant->getGoogleAccount());
		$scope->setType('user');
		$acl_entry = new Zend_Gdata_Docs_AclEntry();
		$acl_entry->setAclRole($role);
		$acl_entry->setAclScope($scope);
		$api->getDocs()->insertAcl($acl_entry, $document);
	}

	/**
	 * @param ilGoogleDocsParticipant           $participant
	 * @param Zend_Gdata_Docs_DocumentListEntry $document
	 * @param int                               $type
	 * @throws InvalidArgumentException
	 */
	public function updateAclEntry(ilGoogleDocsParticipant $participant, Zend_Gdata_Docs_DocumentListEntry $document, $type)
	{
		$api = ilGoogleDocsAPI::getInstance();
		switch($type)
		{
			case self::GDOC_WRITER:
				$api->getDocs()->updateAcl($document, $participant->getGoogleAccount(), 'writer');
				break;

			case self::GDOC_READER:
				$api->getDocs()->updateAcl($document, $participant->getGoogleAccount(), 'reader');
				break;

			default:
				throw new InvalidArgumentException("Role {$type} is currently not supported for the google docs acl list");
				break;
		}
	}
}
