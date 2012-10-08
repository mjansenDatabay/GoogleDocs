<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPlugin.php';
require_once dirname(__FILE__).'/../interfaces/interface.ilGoogleDocsConstants.php';

class ilObjGoogleDocs extends ilObjectPlugin implements ilGoogleDocsConstants
{
	private $doc_type = 0;
	private $doc_url = NULL;
	
	/**
	 * @param int $a_ref_id
	 */
	public function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
	}
	
	/**
 	 *
	 */
	protected function initType()
	{
		$this->setType('xgdo');
	}

	public function setDocType($a_doc_type)
	{
		$this->doc_type = $a_doc_type;
	}
	
	public function getDocType()
	{
		return $this->doc_type;
	}

	public function setDocUrl($a_doc_url)
	{
		$this->doc_url = $a_doc_url;
	}

	public function getDocUrl()
	{
		return $this->doc_url;
	}
	
	/**
 	 * Read data from db
	 */
	public function doCreate()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		if(isset($_POST['doc_type']) && (
			(int)$_POST['doc_type'] == ilGoogleDocsConstants::GOOGLE_DOC 
			|| (int)$_POST['doc_type'] == ilGoogleDocsConstants::GOOGLE_XLS
			|| (int)$_POST['doc_type'] == ilGoogleDocsConstants::GOOGLE_PPT))
		{
			$this->setDocType((int)$_POST['doc_type']);
		}
		else
		{
			// no valid doc_type!!
			//@todo delete created ilias object!
			
			return false;
		}
		
		
		$api = ilGoogleDocsAPI::getInstance();
		$doc_id = $api->createDocumentByType($this->getTitle(),$this->getDocType());

		
		$ilDB->insert('rep_robj_xgdo_data',array(
			'obj_id' => array('integer', $this->getId()),
			'doc_type' => array('integer', $this->getDocType()),
			'doc_url'  => array('text', $doc_id)
		));
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
		}
	}

	/**
	 * Update data
	 */
	public function doUpdate()
	{
	}

	/**
	 * Delete data from database
	 */
	public function doDelete()
	{
		/**
 		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$api = ilGoogleDocsAPI::getInstance();
		
		//https://docs.google.com/feeds/documents/private/full/document%3A1nTlFkBDwkvUqm89JH8K_F_z9YQD2ITTTpPnq975GS3E
		$doc_id = $api->deleteDocumentByUrl($this->getDocUrl());

		$ilDB->manipulate('DELETE FROM rep_robj_xgdo_data WHERE obj_id = ' . $ilDB->quote($this->getId(), 'integer'));
	}

	/**
	 * @param int $new_obj
	 * @param int $a_target_id
	 * @param int $a_copy_id
	 */
	public function doCloneObject($new_obj, $a_target_id, $a_copy_id)
	{
	}
}
