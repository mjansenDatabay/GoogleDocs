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
			if($doc_id)
			{
				sleep(10);
				$api->deleteDocumentByUrl((string)$doc_id);
				ilUtil::sendSuccess($this->pluginObj->txt('created_doc_successfully'));
			}
			else
			{
				ilUtil::sendFailure($this->pluginObj->txt('creating_doc_failed'));
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