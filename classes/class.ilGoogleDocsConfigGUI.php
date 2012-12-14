<?php

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';

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

	public function configure()
	{
		$this->editGoogleDocsSettings();
	}

	public function editGoogleDocsSettings()
	{
		/**
		 * @var $ilCtrl    ilCtrl
		 * @var $lng       ilLanguage
		 * @var $tpl       ilTemplate
		 * @var $ilToolbar ilToolbarGUI
		 */
		global $ilCtrl, $lng, $tpl, $ilToolbar;

		if(ilGoogleDocsAPI::getSetting('login') != NULL && ilGoogleDocsAPI::getSetting('password') != NULL)
		{
			$ilToolbar->addButton($this->pluginObj->txt('check_connection'), $ilCtrl->getLinkTarget($this, 'checkConnection'));
		}

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
		/**
		 * @var $ilCtrl    ilCtrl
		 * @var $lng       ilLanguage
		 * @var $ilToolbar ilToolbar
		 */
		global $ilCtrl, $lng, $ilToolbar;

		$ilToolbar->addButton($lng->txt('settings'), $ilCtrl->getLinkTarget($this, 'editGoogleDocsSettings'));

		$api = ilGoogleDocsAPI::getInstance();

		if(!is_object($api))
		{
			ilUtil::sendFailure($lng->txt('err_check_input'));
			return $this->editGoogleDocsSettings();
		}

		$gd_obj = $api->createDocumentByType('test_doc_title', ilGoogleDocsConstants::GOOGLE_DOC);

		if($gd_obj)
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
		/**
		 * @var $tpl ilTemplate
		 * @var $lng ilLanguage
		 */
		global $tpl, $lng;

		$this->editGoogleDocsSettings();

		if($this->form->checkInput())
		{
			$login    = $this->form->getInput('login');
			$password = $this->form->getInput('password');

			$check = ilGoogleDocsAPI::checkConnection($this->pluginObj, $login, $password);

			if($check == true)
			{
				ilGoogleDocsAPI::setSetting('login', $login);
				ilGoogleDocsAPI::setSetting('password', $password);
				ilUtil::sendSuccess($lng->txt('saved_successfully'));
			}
			else
			{
				ilUtil::sendFailure($lng->txt('err_check_input'));
			}
			$this->form->setValuesByPost();
		}

		return $tpl->setContent($this->form->getHTML());
	}

	public function cancelGoogleDocsSettings()
	{
		$this->editGoogleDocsSettings();
	}
}