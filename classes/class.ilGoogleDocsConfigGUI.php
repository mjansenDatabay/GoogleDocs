<?php

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
require_once dirname(__FILE__).'/../interfaces/interface.ilGoogleDocsConstants.php';

class ilGoogleDocsConfigGUI extends ilPluginConfigGUI implements ilGoogleDocsConstants
{
	/**
	 * @var $pluginObj ilPlugin
	 */
	public $pluginObj = null;
	/**
	 * @var $form ilPropertyFormGUI
	 */
	public $form = null;
	
	public $client = null;

	public function performCommand($cmd)
	{

		$this->pluginObj = ilPlugin::getPluginObject('Services', 'Repository', 'robj', 'GoogleDocs');
		$this->pluginObj->includeClass('class.ilGoogleDocsAPI.php');

		switch ($cmd)
		{
			default:
				$this->$cmd();
				break;
		}
	}

	public function configure()
	{
		$this->editGoogleDocsSettings();
	}

	public function editGoogleDocsSettings()
	{
		global $ilCtrl, $lng, $tpl, $ilToolbar;
		
		// @todo: Show button only if all preconditions are given
		$ilToolbar->addButton($this->pluginObj->txt('check_connection'), $ilCtrl->getLinkTarget($this,'checkConnection'));

			
		include_once './Services/Form/classes/class.ilPropertyFormGUI.php';
		$this->form = new ilPropertyFormGUI();
		
		$this->form->setFormAction($ilCtrl->getFormAction($this, 'saveGoogleDocsSettings'));
		$this->form->setTitle($lng->txt('settings'));

		$this->form->addCommandButton('saveGoogleDocsSettings', $lng->txt('save'));
		$this->form->addCommandButton('cancelGoogleDocsSettings', $lng->txt('cancel'));

		$form_login = new ilTextInputGUI($this->pluginObj->txt('google_login'), 'login');
		$form_login->setRequired(true);
		$form_login->setValue(ilGoogleDocsAPI::getSetting('login'));
		$this->form->addItem($form_login);

		$form_passwd = new ilPasswordInputGUI($lng->txt('password'), 'password');
		$form_passwd->setRequired(true);
		$form_passwd->getSkipSyntaxCheck(true);
		$form_passwd->setValue(ilGoogleDocsAPI::getSetting('password'));
		$form_passwd->setRetype(false);
		$this->form->addItem($form_passwd);

		$tpl->setContent($this->form->getHTML());
	}
	
	public function checkConnection()
	{
		// @todo: Add @var $ilCtrl ilCtrl ..., remove unsused variable
		global $ilCtrl, $lng, $tpl, $ilToolbar;
		
		$ilToolbar->addButton($lng->txt('settings'), $ilCtrl->getLinkTarget($this, 'editGoogleDocsSettings'));

		// @todo: Make the API use more robust (no PHP errors should appear)
		$api = ilGoogleDocsAPI::getInstance();

		// @todo: Remove this?
		//$id = $api->createDocumentByType('test_doc_title', ilGoogleDocsConstants::GOOGLE_DOC);
		// @todo: Hash key?
		$id = $api->deleteDocumentByUrl("https://docs.google.com/feeds/documents/private/full/document%3A1YrgFUyyDdCIYJwD2iVs-MWzHUHmMAHMzsLMpn2ivrXU");
		if($id)
		{
			ilUtil::sendSuccess($this->pluginObj->txt('created_doc_successfully'));
	
		}
		else
		{
			ilUtil::sendFailure($this->pluginObj->txt('creating_doc_failed'));
		}
	}
	
	public function saveGoogleDocsSettings()
	{
		global $tpl, $lng;
		
		$this->editGoogleDocsSettings();
		
		if($this->form->checkInput())
		{
			$login = $this->form->getInput('login');
			$password = $this->form->getInput('password');

			$check = ilGoogleDocsAPI::checkConnection($this->pluginObj, $login, $password);
		
			if($check == true)
			{
				$api = ilGoogleDocsAPI::getInstance();
				$api->setSetting('login', $login);
				$api->setSetting('password', $password);
			}
			else
			{
				return $check;
			}
			return true;
		}
		
		ilUtil::sendFailure($lng->txt('err_check_input'));
		$this->form->setValuesByPost();
		return $tpl->setContent($this->form->getHTML());
	}
	
	public function cancelGoogleDocsSettings()
	{
		$this->editGoogleDocsSettings();
	}

}