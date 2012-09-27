<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPluginGUI.php';
require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

/**
 * @ilCtrl_isCalledBy ilObjGoogleDocsGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls      ilObjGoogleDocsGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilRepositorySearchGUI, ilPublicUserProfileGUI
 */
class ilObjGoogleDocsGUI extends ilObjectPluginGUI
{
	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form;

	/**
	 * @return string
	 */
	public function getType()
	{
		return 'xgdo';
	}

	/**
	 * @return string
	 */
	public function getAfterCreationCmd()
	{
		return 'showContent';
	}

	/**
	 * @return string
	 */
	public function getStandardCmd()
	{
		return 'showContent';
	}

	/**
	 * @param string $cmd
	 */
	public function performCommand($cmd)
	{
		$next_class = $this->ctrl->getNextClass($this);
		switch ($cmd)
		{
			case 'updateProperties':
			case 'editProperties':
				$this->checkPermission('write');
				$this->$cmd();
				break;

			case 'showContent':
				$this->checkPermission('read');
				$this->$cmd();
				break;
		}
	}

	/**
	 *
	 */
	protected function setTabs()
	{
		/**
		 * @var $ilTabs   ilTabsGUI
		 * @var $ilCtrl   ilCtrl
		 * @var $ilAccess ilAccessHandler
		 */
		global $ilTabs, $ilCtrl, $ilAccess;

		$this->addInfoTab();

		if($ilAccess->checkAccess('read', '', $this->object->getRefId()))
		{
			$ilTabs->addTab('content', $this->txt('content'), $ilCtrl->getLinkTarget($this, 'showContent'));
		}

		if($ilAccess->checkAccess('write', '', $this->object->getRefId()))
		{
			$ilTabs->addTab('properties', $this->txt('properties'), $ilCtrl->getLinkTarget($this, 'editProperties'));
		}

		$this->addPermissionTab();
	}

	/**
	 * @param string $a_new_type
	 * @return array
	 */
	protected function initCreationForms($a_new_type)
	{
		$forms = array(
			self::CFORM_NEW   => $this->initCreateForm($a_new_type),
			self::CFORM_CLONE => $this->fillCloneTemplate(null, $a_new_type)
		);

		return $forms;
	}

	/**
	 *
	 */
	public function  initCreateForm($a_new_type)
	{
		$form = parent::initCreateForm($a_new_type);

		return $form;
	}

	/**
	 *
	 */
	protected function showContent()
	{
		/**
		 * @var $tpl ilTemplate
		 * @var $ilTabs ilTabsGUI
		 */
		global $tpl, $ilTabs;

		$ilTabs->activateTab("content");

		$tpl->setContent('Hello World');
	}

	/**
	 *
	 */
	protected function editProperties()
	{
		/**
		 * @var $tpl    ilTemplate
		 * @var $ilTabs ilTabsGUI
		 */
		global $tpl, $ilTabs;

		$ilTabs->activateTab('properties');

		$this->initPropertiesForm();
		$this->getPropertiesValues();

		$tpl->setContent($this->form->getHTML());
	}

	/**
	 *
	 */
	protected function initPropertiesForm()
	{
		/**
		 * @var $ilCtrl ilCtrl
		 */
		global $ilCtrl;

		$this->form = new ilPropertyFormGUI();
		$this->form->setTitle($this->txt('edit_properties'));
		$this->form->setFormAction($ilCtrl->getFormAction($this, 'updateProperties'));

		$ti = new ilTextInputGUI($this->txt('title'), 'title');
		$ti->setRequired(true);
		$this->form->addItem($ti);

		$ta = new ilTextAreaInputGUI($this->txt('description'), 'desc');
		$this->form->addItem($ta);

		$this->form->addCommandButton('updateProperties', $this->txt('save'));
	}

	/**
	 *
	 */
	protected function getPropertiesValues()
	{
		$values['title'] = $this->object->getTitle();
		$values['desc']  = $this->object->getDescription();
		$this->form->setValuesByArray($values);
	}

	/**
 	 *
	 */
	protected function updateProperties()
	{
		/**
		 * @var $tpl    ilTemplate
		 * @var $lng    ilLanguage
		 * @var $ilCtrl ilCtrl
		 */
		global $tpl, $lng, $ilCtrl;

		$this->initPropertiesForm();
		if($this->form->checkInput())
		{
			$this->object->setTitle($this->form->getInput('title'));
			$this->object->setDescription($this->form->getInput('desc'));
			$this->object->update();
			ilUtil::sendSuccess($lng->txt('msg_obj_modified'), true);
			$ilCtrl->redirect($this, 'editProperties');
		}

		$this->form->setValuesByPost();

		$tpl->setContent($this->form->getHtml());
	}
}
