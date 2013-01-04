<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPluginAccess.php';

/**
 * 
 */
class ilObjGoogleDocsAccess extends ilObjectPluginAccess
{
	/**
	 * @param string $a_cmd
	 * @param string $a_permission
	 * @param int    $a_ref_id
	 * @param int    $a_obj_id
	 * @param string $a_user_id
	 * @return bool
	 */
	public function _checkAccess($a_cmd, $a_permission, $a_ref_id, $a_obj_id, $a_user_id = '')
	{
		/**
 		 * @var $ilUser ilObjUser
		 * @var $ilObjDataCache ilObjectDataCache
		 */
		global $ilUser, $ilObjDataCache;

		if(!$a_user_id)
		{
			$a_user_id = $ilUser->getId();
		}

		if($a_user_id == $ilObjDataCache->lookupOwner($a_obj_id))
		{
			return true;
		}
		
		switch($a_permission)
		{
			case 'write':
			case 'delete':
			case 'visible':
			case 'edit_permission':
			case 'read':
				return true;
		}

		return false;
	}

	/**
	 * @param int $a_user_id
	 * @param int $a_ref_id
	 * @return bool
	 */
	public static function _hasReaderRole($a_user_id, $a_ref_id)
	{
		/**
		 *  @var $rbacreview	ilRbacReview
		 */
		global $rbacreview;

		$role_folder = $rbacreview->getRoleFolderOfObject($a_ref_id);
		$roles = $rbacreview->getRoleListByObject($role_folder['child']);
		foreach($roles as $role)
		{
			if(strpos($role['title'], 'il_xgdo_reader') !== false)
			{
				return $rbacreview->isAssigned($a_user_id, $role['rol_id']);
			}
		}
		
		return false;
	}

	/**
	 * @param int $a_user_id
	 * @param int $a_ref_id
	 * @return bool
	 */
	public static function _hasWriterRole($a_user_id, $a_ref_id)
	{
		/**
		 * @var $rbacreview ilRbacReview
		 */
		global $rbacreview;

		$role_folder = $rbacreview->getRoleFolderOfObject($a_ref_id);
		$roles = $rbacreview->getRoleListByObject($role_folder['child']);
		foreach($roles as $role)
		{
			if(strpos($role['title'], 'il_xgdo_writer') !== false)
			{
				return $rbacreview->isAssigned($a_user_id, $role['rol_id']);
			}
		}

		return false;
	}

	/**
	 * @param array $a_obj_ids
	 * @param array $a_ref_ids
	 */
	public function _preloadData(array $a_obj_ids, array $a_ref_ids)
	{
		/**
		 * @var $ilObjDataCache ilObjectDataCache
		 */
		global $ilObjDataCache;

		$ilObjDataCache->preloadObjectCache($a_obj_ids);
	}
}
