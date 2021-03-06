<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilRepositoryObjectPlugin.php';
set_include_path(implode(DIRECTORY_SEPARATOR, array(dirname(dirname(__FILE__)), 'library')) . PATH_SEPARATOR . get_include_path());
require_once 'Zend/Loader.php';

Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_Docs');
Zend_Loader::loadClass('Zend_Gdata_Spreadsheets');
Zend_Loader::loadClass('Zend_Gdata_App_AuthException');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Http_Client_Exception');
Zend_Loader::loadClass('Zend_Http_Client');
Zend_Loader::loadClass('Zend_Http_Client_Adapter_Proxy');
Zend_Loader::loadClass('Zend_Gdata_Batch');
Zend_Loader::loadClass('Zend_Gdata_Batch_Id');
Zend_Loader::loadClass('Zend_Gdata_Batch_Operation');
Zend_Loader::loadClass('Zend_Gdata_Batch_Status');
Zend_Loader::loadClass('Zend_Gdata_Acl_Role');
Zend_Loader::loadClass('Zend_Gdata_Acl_Scope');
Zend_Loader::loadClass('Zend_Gdata_Docs_AclEntry');
Zend_Loader::loadClass('Zend_Gdata_Docs_AclFeed');
Zend_Loader::loadClass('Zend_Gdata_Docs_AclQuery');
Zend_Loader::loadClass('Zend_Gdata_Docs_BatchAclEntry');
Zend_Loader::loadClass('Zend_Gdata_Docs_BatchAclFeed');

/**
 *
 */
class ilGoogleDocsPlugin extends ilRepositoryObjectPlugin
{
	/**
	 * @var string
	 */
	const CTYPE = 'Services';

	/**
	 * @var string
	 */
	const CNAME = 'Repository';

	/**
	 * @var string
	 */
	const SLOT_ID = 'robj';

	/**
	 * @var string
	 */
	const PNAME = 'GoogleDocs';

	/**
	 * @var ilGoogleDocsPlugin|null
	 */
	private static $instance = null;

	/**
	 * @return ilGoogleDocsPlugin
	 */
	public static function getInstance()
	{
		if(null === self::$instance)
		{
			require_once 'Services/Component/classes/class.ilPluginAdmin.php';
			return self::$instance = ilPluginAdmin::getPluginObject(
				self::CTYPE,
				self::CNAME,
				self::SLOT_ID,
				self::PNAME
			);
		}

		return self::$instance;
	}

	/**
	 * @return string
	 */
	public function getPluginName()
	{
		return self::PNAME;
	}

	/**
	 * 
	 */
	protected function uninstallCustom()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		if($ilDB->tableExists('rep_robj_xgdo_data'))
		{
			$ilDB->dropTable('rep_robj_xgdo_data');
		}

		if($ilDB->tableExists('rep_robj_xgdo_settings'))
		{
			$ilDB->dropTable('rep_robj_xgdo_settings');
		}

		if($ilDB->tableExists('rep_robj_xgdo_members'))
		{
			$ilDB->dropTable('rep_robj_xgdo_members');
		}

		foreach(array('il_xgdo_reader', 'il_xgdo_writer') as $tpl)
		{
			$obj_ids = ilObject::_getIdsForTitle($tpl, 'rolt');
			foreach($obj_ids as $obj_id)
			{
				$obj = ilObjectFactory::getInstanceByObjId($obj_id, false);
				if(!($obj instanceof ilObjRoleTemplate))
				{
					continue;
				}
				$obj->delete();
			}
		}
	}
}
