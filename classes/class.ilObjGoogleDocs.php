<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPlugin.php';
require_once dirname(__FILE__).'/../interfaces/interface.ilGoogleDocsConstants.php';


class ilObjGoogleDocs extends ilObjectPlugin implements ilGoogleDocsConstants
{
	private $doc_type = 0;
	private $doc_url = NULL;
	private $edit_doc_url = NULL;
	
	/**
	 * @param int $a_ref_id
	 */
	public function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
		$this->plugin->includeClass('class.ilGoogleDocsAPI.php');
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

	/**
	 * @param string $a_doc_url
	 */
	public function setDocUrl($a_doc_url)
	{
		$this->doc_url = $a_doc_url;
	}

	public function getDocUrl()
	{
		return $this->doc_url;
	}

	/**
	 * @param $a_edit_doc_url string url for editing the doc
	 */
	public function setEditDocUrl($a_edit_doc_url)
	{
		$this->edit_doc_url = $a_edit_doc_url;
	}
	public function getEditDocUrl()
	{
		return $this->edit_doc_url;
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
	
		$document = $api->docs->getDocumentListEntry($doc_id->getText());
		foreach($document->getLink() as $link)
		{
			if($link->getRel() == 'alternate')
			{
				$edit_doc_url = $link->getHref();
			}
		}	
		
		$ilDB->insert('rep_robj_xgdo_data',array(
			'obj_id' => array('integer', $this->getId()),
			'doc_type' => array('integer', $this->getDocType()),
			'doc_url'  => array('text', $doc_id),
			'edit_doc_url' => array('text', $edit_doc_url)
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
	 * Delete data from database
	 */
	public function doDelete()
	{
		/**
 		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$api = ilGoogleDocsAPI::getInstance();
		
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
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;
		
		$res = $ilDB->queryF('SELECT * FROM rep_robj_xgdo_data WHERE obj_id = %s',
		array('integer'), array($this->getId()));
		
		while($row = $ilDB->fetchAssoc($res))
		{
			$source = $row;
		}
		
		$api = ilGoogleDocsAPI::getInstance();
		

		$new_doc = $api->copyDocument($source['doc_url'], $source['doc_type'], $new_obj->getTitle());
		$new_doc_id = $new_doc->getText();
		$document = $api->docs->getDocumentListEntry($new_doc_id);	
		foreach($document->getLink() as $link)
		{
			if($link->getRel() == 'alternate')
			{
				$new_edit_doc_url = $link->getHref();
			}
		}	
		
		$ilDB->insert('rep_robj_xgdo_data', array(
			'obj_id' => array('integer', $new_obj->getId()),
			'doc_type' => array('integer', $source['doc_type']),
			'doc_url' => array('text', $new_doc_id),
			'edit_doc_url' => array('text', $new_edit_doc_url)
		));

	}
}
