<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilRepositoryObjectPlugin.php';
set_include_path(implode(DIRECTORY_SEPARATOR, array(dirname(dirname(__FILE__)), 'library')) . PATH_SEPARATOR . get_include_path());
require_once 'Zend/Loader.php';

Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_Docs');
Zend_Loader::loadClass('Zend_Gdata_App_AuthException');
Zend_Loader::loadClass('Zend_Gdata_AuthSub');
Zend_Loader::loadClass('Zend_Http_Client_Exception');
Zend_Loader::loadClass('Zend_Http_Client');
Zend_Loader::loadClass('Zend_Http_Client_Adapter_Proxy');

/**
 * 
 */
class ilGoogleDocsPlugin extends ilRepositoryObjectPlugin
{
	/**
	 * @return string
	 */
	public function getPluginName()
	{
		return 'GoogleDocs';
	}
}
