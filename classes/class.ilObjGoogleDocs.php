<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPlugin.php';

class ilObjGoogleDocs extends ilObjectPlugin
{
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

	/**
 	 * Read data from db
	 */
	public function doCreate()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$ilDB->manipulate('
			INSERT INTO rep_robj_xgdo_data
			(obj_id)
			VALUES ( ' .
				$ilDB->quote($this->getId(), 'integer') .
			')'
		);
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
