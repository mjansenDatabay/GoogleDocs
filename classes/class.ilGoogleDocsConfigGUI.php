<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
require_once 'Services/Component/classes/class.ilPluginConfigGUI.php';
require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';

/**
 * 
 */
class ilGoogleDocsConfigGUI extends ilPluginConfigGUI implements ilGoogleDocsConstants
{
	/**
	 * @var ilPlugin
	 */
	public $pluginObj = null;

	/**
	 * @var ilPropertyFormGUI
	 */
	public $form = null;

	/**
	 * @param string $cmd
	 */
	public function performCommand($cmd)
	{
		$this->pluginObj = ilPlugin::getPluginObject('Services', 'Repository', 'robj', 'GoogleDocs');
		$this->pluginObj->includeClass('class.ilGoogleDocsAPI.php');

		switch($cmd)
		{
			default:
				$this->$cmd();
				break;
		}
	}

	/**
	 * 
	 */
	public function configure()
	{
		$this->editGoogleDocsSettings();
	}

	/**
	 * 
	 */
	public function editGoogleDocsSettings()
	{
		/**
		 * @var $ilCtrl    ilCtrl
		 * @var $lng       ilLanguage
		 * @var $tpl       ilTemplate
		 * @var $ilToolbar ilToolbarGUI
		 */
		global $ilCtrl, $lng, $tpl, $ilToolbar;

		if(ilGoogleDocsAPI::getSetting('login') != NULL &&
		   ilGoogleDocsAPI::getSetting('password') != NULL)
		{
			$ilToolbar->addButton($this->pluginObj->txt('check_connection'), $ilCtrl->getLinkTarget($this, 'checkConnection'));
		}

		$this->form = new ilPropertyFormGUI();

		$this->form->setFormAction($ilCtrl->getFormAction($this, 'saveGoogleDocsSettings'));
		$this->form->setTitle($lng->txt('settings'));

		$this->form->addCommandButton('saveGoogleDocsSettings', $lng->txt('save'));

		$form_login = new ilTextInputGUI($this->pluginObj->txt('google_login'), 'login');
		$form_login->setRequired(true);
		$form_login->setValue(ilGoogleDocsAPI::getSetting('login'));

		$form_passwd = new ilPasswordInputGUI($lng->txt('password'), 'password');
		$form_passwd->setRequired(true);
		$form_passwd->getSkipSyntaxCheck(true);
		$form_passwd->setValue(ilGoogleDocsAPI::getSetting('password'));
		$form_passwd->setRetype(false);

		$this->form->addItem($form_login);
		$this->form->addItem($form_passwd);

		$tpl->setContent($this->form->getHTML());
	}

	/**
	 * 
	 */
	public function checkConnection()
	{
		/**
		 * @var $ilCtrl    ilCtrl
		 * @var $lng       ilLanguage
		 * @var $ilToolbar ilToolbarGUI
		 */
		global $ilCtrl, $lng, $ilToolbar;

		if(ilGoogleDocsAPI::getSetting('login') == NULL ||
		   ilGoogleDocsAPI::getSetting('password') == NULL
		)
		{
			$this->editGoogleDocsSettings();
			return;
		}

		$ilToolbar->addButton($lng->txt('back'), $ilCtrl->getLinkTarget($this, 'editGoogleDocsSettings'));

		try
		{
			$api    = ilGoogleDocsAPI::getInstance();
			$doc_id = $api->createDocumentByType('test_doc_title', self::GOOGLE_DOC);
			//$document = $api->getDocs()->getDocumentListEntry($doc_id->getText());

			// ACL-Liste
			//$response = $api->docs->get($document->getId().'/acl');
			//echo(htmlspecialchars($response->getBody()));

			//$GLOBALS["debug"] = TRUE;
			
			/*$role = new Zend_Gdata_Acl_Role();
			$role->setValue('writer');
			
			$scope = new Zend_Gdata_Acl_Scope();
			$scope->setValue('bheyser@databay.de');
			$scope->setType('user');
			
			$acl_entry = new Zend_Gdata_Docs_AclEntry();
			$acl_entry->setAclRole($role);
			$acl_entry->setService($scope);
			
			$api->getDocs()->insertAcl($acl_entry, $document);
			//var_dump($api->getDocs()->getAclFeed($document));*/
			
			/*var_dump($api->docs->post('
			<entry xmlns="http://www.w3.org/2005/Atom" xmlns:gAcl="http://schemas.google.com/acl/2007">
  				<category scheme="http://schemas.google.com/g/2005#kind" term="http://schemas.google.com/acl/2007#accessRule"/>
  				<gAcl:role value="owner"/>
  				<gAcl:scope type="user" value="brainstorm0815@yahoo.de"/>
			</entry>', str_replace(array('documents'),array('acl'), $document->getId())));*/

			//https://developers.google.com/google-apps/documents-list/
			
			//https://docs.google.com/feeds/acl/private/full/document%3A1kiJmb2nqVWA7MefiEoCZ0wNwPzL1JFILX2-SadBIEvQ/acl*/
			//https://docs.google.com/feeds/acl/private/full/document%3A1fEgXr-mt8hlWyYU2CHh0zUgiM5payBhuKvtxuChi7n8

			//var_dump($api->getDocs()->getAclFeed($document));
			
			//exit();
			//$GLOBALS["debug"] = false;
			if($doc_id)
			{
				$api->deleteDocumentByUrl((string)$doc_id);
				ilUtil::sendSuccess($this->pluginObj->txt('connection_check_successful'));
			}
			else
			{
				ilUtil::sendFailure($this->pluginObj->txt('connection_check_failed'));
			}
		}
		catch(Exception $e)
		{
			ilUtil::sendFailure($e->getMessage());
		}
	}

	/**
	 * 
	 */
	public function saveGoogleDocsSettings()
	{
		/**
		 * @var $tpl    ilTemplate
		 * @var $lng    ilLanguage
		 * @var $ilCtrl ilCtrl
		 */
		global $tpl, $lng, $ilCtrl;

		$this->editGoogleDocsSettings();

		if($this->form->checkInput())
		{
			$login    = $this->form->getInput('login');
			$password = $this->form->getInput('password');
			
			try
			{
				if(ilGoogleDocsAPI::checkConnection($this->pluginObj, $login, $password))
				{
					ilGoogleDocsAPI::setSetting('login', $login);
					ilGoogleDocsAPI::setSetting('password', $password);
					ilUtil::sendSuccess($lng->txt('saved_successfully'), true);
					$ilCtrl->redirect($this, 'editGoogleDocsSettings');
				}
				else
				{
					ilUtil::sendFailure($lng->txt('err_check_input'));
				}
			}
			catch(Exception $e)
			{
				ilUtil::sendFailure($e->getMessage());
			}
			
			$this->form->setValuesByPost();
		}

		$tpl->setContent($this->form->getHTML());
	}
}