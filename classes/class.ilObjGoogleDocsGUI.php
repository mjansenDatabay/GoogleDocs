<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPluginGUI.php';
require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';

require_once dirname(__FILE__).'/../interfaces/interface.ilGoogleDocsConstants.php';

/**
 * @ilCtrl_isCalledBy ilObjGoogleDocsGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls      ilObjGoogleDocsGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilRepositorySearchGUI, ilPublicUserProfileGUI, ilCommonActionDispatcherGUI
 */
class ilObjGoogleDocsGUI extends ilObjectPluginGUI implements ilGoogleDocsConstants
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
	 * @param string $a_new_type
	 * @return ilPropertyFormGUI
	 */

	public function  initCreateForm($a_new_type)
	{
		$form = parent::initCreateForm($a_new_type);
		 
		$radio_doctype = new ilRadioGroupInputGUI($this->plugin->txt('doctype'), 'doc_type');
		$radio_doctype->setRequired(true);
		$option_1 = new ilRadioOption($this->plugin->txt('doctype_doc'), ilGoogleDocsConstants::GOOGLE_DOC);
		$option_2 = new ilRadioOption($this->plugin->txt('doctype_xls'), ilGoogleDocsConstants::GOOGLE_XLS);
		$option_3 = new ilRadioOption($this->plugin->txt('doctype_ppt'), ilGoogleDocsConstants::GOOGLE_PPT);

		$radio_doctype->addOption($option_1);
		$radio_doctype->addOption($option_2);
		$radio_doctype->addOption($option_3);
	
		$form->addItem($radio_doctype);

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
		 * @var $lng ilLanguage
		 */
		global $tpl, $ilTabs, $lng;

		$this->plugin->includeClass('class.ilGoogleDocsAPI.php');
		
		$api = ilGoogleDocsAPI::getInstance();
		
		$ilTabs->activateTab("content");
		
		require_once 'Services/jQuery/classes/class.iljQueryUtil.php';
		iljQueryUtil::initjQuery();
		iljQueryUtil::initjQueryUI();
		
		$tpl->addCss("./Customizing/global/plugins/Services/Repository/RepositoryObject/GoogleDocs/templates/css/jquery-ui-1.9.0.custom.css");
		
		$form =  new ilPropertyFormGUI();
		
		$url = new ilCustomInputGUI($lng->txt('url'), 'edit_url'); 
		$href = '<a href="'.$this->object->getEditDocUrl().'" target="_blank" >'.$this->object->getEditDocUrl().'</a>';
		$url->setHtml($href);

		$form->addItem($url);

		$html = $form->getHTML();
		
		$id = substr(md5($this->object->getEditDocUrl()), 0, 8);
		
		$html .= '
		<div id="resizable'.$id.'" style="height:600px;padding:20px">
		<iframe id="iframe'.$id.'" src="'.$this->object->getEditDocUrl().'" 
			style="border:none" name="'.md5($this->object->getTitle).'">
			<p>Ihr Browser kann leider keine eingebetteten Frames anzeigen:
				Sie k&ouml;nnen die eingebettete Seite &uuml;ber den folgenden Verweis
			aufrufen: <a href=""'.$this->object->getEditDocUrl().'"" >""'.$this->object->getEditDocUrl().'</a></p>
		</iframe></div>
		<script type="text/javascript">
		$(function() {
			$( "#resizable'.$id.'" ).css({
				"width": $("#resizable'.$id.'").width()
			});
			$( "#resizable'.$id.'" ).resizable();
			$( "#iframe'.$id.'" ).css({
				"width": "100%",
				"height": "100%",
			});
		});
		</script>
		';
		
		$tpl->setContent($html);
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

		$doc_info = new ilNonEditableValueGUI($this->plugin->txt('doctype'), 'doc_type');
		$this->form->addItem($doc_info);

		$this->form->addCommandButton('updateProperties', $this->txt('save'));
	}

	/**
	 *
	 */
	protected function getPropertiesValues()
	{
		$values['title'] = $this->object->getTitle();
		$values['desc']  = $this->object->getDescription();

		if($this->object->getDocType() == ilGoogleDocsConstants::GOOGLE_DOC)
			$doc_type = $this->plugin->txt('doctype_doc');

		elseif($this->object->getDocType() == ilGoogleDocsConstants::GOOGLE_XLS)
			$doc_type = $this->plugin->txt('doctype_xls');

		elseif($this->object->getDocType() == ilGoogleDocsConstants::GOOGLE_PPT)
			$doc_type = $this->plugin->txt('doctype_ppt');
	
		else
		{
			$doc_type= $this->plugin->txt('doctype_not_valid');
		}
		
		$values['doc_type'] = $doc_type;

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
