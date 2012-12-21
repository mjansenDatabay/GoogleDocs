<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPlugin.php';
require_once dirname(__FILE__).'/../interfaces/interface.ilGoogleDocsConstants.php';

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
		$query = "SELECT obj_id FROM object_data WHERE type = %s AND title = %s";

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
		$query = "SELECT obj_id FROM object_data WHERE type = %s AND title = %s";

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
	 * @param array $a_user_ids
	 * @param int $a_type
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function addParticipants(array $a_user_ids, $a_type)
	{
		/**
		 * @var $rbacadmin ilRbacAdmin
		 */
		global $rbacadmin;

		foreach((array) $a_user_ids as $user_id)
		{
			switch($a_type)
			{
				case self::GDOC_WRITER:
					$writer = $this->getDefaultWriterRole();
					if($writer)
					{
						$rbacadmin->assignUser(
							$writer,
							$user_id
						);
						// @todo: Add writer permission via api
					}
					break;

				case self::GDOC_READER:
					$reader = $this->getDefaultReaderRole();
					if($reader)
					{
						$rbacadmin->assignUser(
							$reader,
							$user_id
						);
						// @todo: Add reader permission via api
					}
					break;

				default:
					throw new InvalidArgumentException(
						'Invalid role type given'
					);
					break;
			}
		}

		return true;
	}

	/**
	 * @return int
	 */
	public function getDefaultWriterRole()
	{
		$local_roles = $this->getLocalRoles();
		if(isset($local_roles['il_xgdo_writer_'.$this->getRefId()]))
		{
			return $local_roles['il_xgdo_writer_'.$this->getRefId()];
		}

		return 0;
	}

	/**
	 * @return int
	 */
	public function getDefaultReaderRole()
	{
		$local_roles = $this->getLocalRoles();
		if(isset($local_roles['il_xgdo_reader_'.$this->getRefId()]))
		{
			return $local_roles['il_xgdo_reader_'.$this->getRefId()];
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
		)))
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
		 * @var $ilDB  ilDB
		 * @var $ilLog ilLog
		 */
		global $ilDB, $ilLog;

		$this->setDocType((int)$_POST['doc_type']);

		try
		{
			$api      = ilGoogleDocsAPI::getInstance();
			$doc_id   = $api->createDocumentByType($this->getTitle(), $this->getDocType());
			$document = $api->docs->getDocumentListEntry($doc_id->getText());
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

			$ilDB->insert(
				'rep_robj_xgdo_data',
				array(
					'obj_id'       => array('integer', $this->getId()),
					'doc_type'     => array('integer', $this->getDocType()),
					'doc_url'      => array('text', $doc_id),
					'edit_doc_url' => array('text', $edit_doc_url)
				)
			);

			$role = new Zend_Gdata_Acl_Role();
			$role->setValue('writer');
			$scope = new Zend_Gdata_Acl_Scope();
			// @todo: Add google account for owner
			$scope->setValue('mjansen@databay.de');
			$scope->setType('user');
			$acl_entry = new Zend_Gdata_Docs_AclEntry();
			$acl_entry->setAclRole($role);
			$acl_entry->setAclScope($scope);
			$api->getDocs()->insertAcl($acl_entry, $document);
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
	}

	/**
	 * @param ilObjGoogleDocs $new_obj
	 * @param int             $a_target_id
	 * @param int             $a_copy_id
	 */
	public function doCloneObject($new_obj, $a_target_id, $a_copy_id)
	{
		/**
		 * @var $ilDB  ilDB
		 * @var $ilLog ilLog
		 */
		global $ilDB, $ilLog;

		$res    = $ilDB->queryF(
			'SELECT * FROM rep_robj_xgdo_data WHERE obj_id = %s',
			array('integer'),
			array($this->getId())
		);
		$source = array();
		while($row = $ilDB->fetchAssoc($res))
		{
			$source = $row;
		}

		try
		{
			$api        = ilGoogleDocsAPI::getInstance();
			$new_doc    = $api->copyDocument($source['doc_url'], $source['doc_type'], $new_obj->getTitle());
			$new_doc_id = $new_doc->getText();
			$document   = $api->docs->getDocumentListEntry($new_doc_id);
			foreach($document->getLink() as $link)
			{
				/**
				 * @var $link Zend_Gdata_App_Extension_link
				 */

				if($link->getRel() == 'alternate')
				{
					$new_edit_doc_url = $link->getHref();
				}
			}

			$ilDB->insert(
				'rep_robj_xgdo_data',
				array(
					'obj_id'       => array('integer', $new_obj->getId()),
					'doc_type'     => array('integer', $source['doc_type']),
					'doc_url'      => array('text', $new_doc_id),
					'edit_doc_url' => array('text', $new_edit_doc_url)
				)
			);
		}
		catch(Exception $e)
		{
			//@todo: Handle Exception
			$ilLog->write($e->getMessage());
			$ilLog->logStack();
		}
	}
}
