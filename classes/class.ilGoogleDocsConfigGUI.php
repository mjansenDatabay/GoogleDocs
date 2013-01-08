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
	 * @var ilGoogleDocsPlugin
	 */
	protected $pluginObj = null;

	/**
	 * @var ilPropertyFormGUI
	 */
	protected $configuration_form = null;

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

		if(null != ilGoogleDocsAPI::getSetting('login') && null != ilGoogleDocsAPI::getSetting('password'))
		{
			$ilToolbar->addButton($this->pluginObj->txt('check_connection'), $ilCtrl->getLinkTarget($this, 'checkConnection'));
		}

		$form = $this->getConfigurationForm();
		$tpl->setContent($form->getHTML());
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getConfigurationForm()
	{
		/**
		 * @var $ilCtrl    ilCtrl
		 * @var $lng       ilLanguage
		 */
		global $ilCtrl, $lng;

		if($this->configuration_form instanceof ilPropertyFormGUI)
		{
			return $this->configuration_form;
		}

		$this->configuration_form = new ilPropertyFormGUI();
		$this->configuration_form->setFormAction($ilCtrl->getFormAction($this, 'saveGoogleDocsSettings'));
		$this->configuration_form->setTitle($lng->txt('settings'));

		$form_login = new ilTextInputGUI($this->pluginObj->txt('google_master_login'), 'login');
		$form_login->setRequired(true);
		$form_login->setInfo($this->pluginObj->txt('google_master_login_info'));
		$form_login->setValue(ilGoogleDocsAPI::getSetting('login'));

		$form_passwd = new ilPasswordInputGUI($lng->txt('password'), 'password');
		$form_passwd->setRequired(true);
		$form_passwd->getSkipSyntaxCheck(true);
		$form_passwd->setValue(ilGoogleDocsAPI::getSetting('password'));
		$form_passwd->setRetype(false);

		$this->configuration_form->addItem($form_login);
		$this->configuration_form->addItem($form_passwd);
		$this->configuration_form->addCommandButton('saveGoogleDocsSettings', $lng->txt('save'));

		return $this->configuration_form;
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

		if(null == ilGoogleDocsAPI::getSetting('login') || null == ilGoogleDocsAPI::getSetting('password'))
		{
			$this->editGoogleDocsSettings();
			return;
		}

		$ilToolbar->addButton($lng->txt('back'), $ilCtrl->getLinkTarget($this, 'editGoogleDocsSettings'));

		try
		{
			$doc_id = ilGoogleDocsAPI::getInstance()->createDocumentByType('test_doc_title', self::DOC_TYPE_DOCUMENT);
			if($doc_id)
			{
				ilGoogleDocsAPI::getInstance()->deleteDocumentByUrl((string)$doc_id);
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

		$form = $this->getConfigurationForm();
		if($form->checkInput())
		{
			$login    = $form->getInput('login');
			$password = $form->getInput('password');

			try
			{
				ilGoogleDocsAPI::checkConnection($login, $password);
				ilGoogleDocsAPI::setSetting('login', $login);
				ilGoogleDocsAPI::setSetting('password', $password);
				ilUtil::sendSuccess($lng->txt('saved_successfully'), true);
				$ilCtrl->redirect($this, 'editGoogleDocsSettings');
			}
			catch(Exception $e)
			{
				ilUtil::sendFailure($e->getMessage());
			}
		}

		$form->setValuesByPost();
		$tpl->setContent($form->getHTML());
	}
}