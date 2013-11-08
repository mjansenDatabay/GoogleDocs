<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPluginGUI.php';
require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';
require_once dirname(__FILE__) . '/class.ilGoogleDocsParticipantsTableGUI.php';
require_once dirname(__FILE__) . '/class.ilGoogleDocsParticipant.php';
require_once dirname(__FILE__) . '/class.ilGoogleDocsParticipants.php';
require_once dirname(__FILE__) . '/Form/class.ilGoogleAccountInputGUI.php';
require_once 'Services/PersonalDesktop/interfaces/interface.ilDesktopItemHandling.php';

/**
 * @ilCtrl_isCalledBy ilObjGoogleDocsGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls      ilObjGoogleDocsGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilRepositorySearchGUI, ilPublicUserProfileGUI, ilCommonActionDispatcherGUI, ilExportGUI, ilMDEditorGUI
 */
class ilObjGoogleDocsGUI extends ilObjectPluginGUI implements ilGoogleDocsConstants, ilDesktopItemHandling
{
	/**
	 * @var ilObjGoogleDocs
	 */
	public $object = null;

	/**
	 * @var ilPropertyFormGUI
	 */
	protected $form = null;

	/**
	 * @var ilPropertyFormGUI
	 */
	protected $personal_settings_form = null;

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
		/**
		 * @var $ilTabs ilTabsGUI
		 * @var $tpl    ilTemplate
		 */
		global $ilTabs, $tpl;

		$tpl->setDescription($this->object->getDescription());

		$next_class = $this->ctrl->getNextClass($this);
		switch($next_class)
		{
			case 'ilmdeditorgui':
				$this->checkPermission('write');
				require_once 'Services/MetaData/classes/class.ilMDEditorGUI.php';
				$md_gui = new ilMDEditorGUI($this->object->getId(), 0, $this->object->getType());
				$md_gui->addObserver($this->object, 'MDUpdateListener', 'General');
				$ilTabs->setTabActive('meta_data');
				$this->ctrl->forwardCommand($md_gui);
				return;
				break;

			case 'ilpublicuserprofilegui':
				$ilTabs->activateTab('members');

				$this->setSubTabs('members');

				require_once 'Services/User/classes/class.ilPublicUserProfileGUI.php';
				$profile_gui = new ilPublicUserProfileGUI($_GET["user"]);
				$profile_gui->setBackUrl($this->ctrl->getLinkTarget($this, 'showParticipantsGallery'));
				$this->tpl->setContent($this->ctrl->forwardCommand($profile_gui));
				break;

			case 'ilcommonactiondispatchergui':
				require_once 'Services/Object/classes/class.ilCommonActionDispatcherGUI.php';
				$gui = ilCommonActionDispatcherGUI::getInstanceFromAjaxCall();
				$this->ctrl->forwardCommand($gui);
				break;

			case 'ilrepositorysearchgui':
				$this->checkPermission('write');
				$ilTabs->activateTab('members');

				$this->setSubTabs('members');

				require_once 'Services/Search/classes/class.ilRepositorySearchGUI.php';
				$rep_search = new ilRepositorySearchGUI();
				$rep_search->setCallback($this,
					'addParticipants',
					array(
						self::GDOC_READER => $this->plugin->txt('reader_short_add'),
						self::GDOC_WRITER => $this->plugin->txt('writer_short_add')
					)
				);
				$this->ctrl->setReturn($this, 'editParticipants');
				$this->ctrl->forwardCommand($rep_search);
				break;

			case 'ilexportgui':
				$this->checkPermission('write');
				$ilTabs->activateTab('export');

				include_once 'Services/Export/classes/class.ilExportGUI.php';
				$exp = new ilExportGUI($this);
				$this->addSupportedExportFormats($exp);
				$this->ctrl->forwardCommand($exp);
				break;

			default:
				switch($cmd)
				{
					case 'sendMailToSelectedUsers':
					case 'deleteParticipants':
					case 'confirmDeleteParticipants':
					case 'addParticipants':
					case 'editParticipants':
					case 'updateProperties':
					case 'editProperties':
						$this->checkPermission('write');
						$this->$cmd();
						break;

					case 'join':
						$this->checkPermission('visible');
						$this->$cmd();

					case 'redrawHeaderAction':
					case 'addToDesk':
					case 'saveGoogleAccount':
					case 'removeFromDesk':
					case 'editPersonalSettings':
					case 'updatePersonalSettings':
					case 'showParticipantsGallery':
					case 'showContent':
						if(in_array($cmd, array('addToDesk', 'removeFromDesk')))
						{
							$cmd .= 'Object';
						}
						$this->checkPermission('read');
						$this->$cmd();
						break;

					default:
						if(!method_exists($this, $cmd))
						{
							$this->$cmd();
						}
						break;
				}
				break;
		}

		$this->addHeaderAction();
	}

	/**
	 *
	 */
	public function join()
	{
		/**
		 * @var $ilCtrl ilCtrl
		 * @var $ilUser ilObjUser
		 * @var $ilLog  ilLog
		 */
		global $ilCtrl, $ilUser, $ilLog;

		if(
			ilObjGoogleDocsAccess::_hasReaderRole($ilUser->getId(), $this->ref_id)
			||
			ilObjGoogleDocsAccess::_hasWriterRole($ilUser->getId(), $this->ref_id)
		)
		{
			ilUtil::sendInfo($this->txt('already_member'), true);
			$ilCtrl->redirect($this, 'showContent');
		}

		try
		{
			$participant = ilGoogleDocsParticipant::getInstanceByObjId($this->object->getId(), $ilUser->getId());
			$participant->add(self::GDOC_READER);
		}
		catch(Exception $e)
		{
			//@todo: Handle Exception
			$ilLog->write($e->getMessage());
			$ilLog->logStack();
			ilUtil::sendFailure($e->getMessage(), true);
			$ilCtrl->redirect($this, 'infoScreen');
		}

		ilUtil::sendSuccess($this->plugin->txt('joined_successfully'), true);
		$ilCtrl->redirect($this, 'showContent');
	}

	/**
	 * @param  string $name
	 * @param array   $arguments
	 * @throws RuntimeException
	 */
	public function __call($name, array $arguments)
	{
		$method_parts = explode('_', strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $name)));
		if('export' == $method_parts[0])
		{
			$this->performExport($method_parts[2]);
			return;
		}

		throw new RuntimeException("Called method {$name} currently not supported");
	}

	/**
	 * @param string $type
	 */
	protected function performExport($type)
	{
		/**
		 * @var $ilLog ilLog
		 */
		global $ilLog;

		$this->checkPermission('write');

		try
		{
			$response = $this->object->getExportResponse($type);

			include_once 'Services/Export/classes/class.ilExport.php';
			ilExport::_createExportDirectory($this->object->getId(), $type, $this->object->getType());
			$export_directory = ilExport::_getExportDirectory($this->object->getId(), $type, $this->object->getType());
			$ts               = time();

			$sub_dir        = $ts . '__' . IL_INST_ID . '__' . $this->object->getType() . '_' . $this->object->getId();
			$new_file       = $sub_dir . '.zip';
			$export_run_dir = $export_directory . '/' . $sub_dir;

			ilUtil::makeDirParents($export_run_dir);

			$matches = null;
			preg_match('/filename="(.*\.[a-zA-Z]{3,4})".*$/', $response->getHeader('Content-disposition'), $matches);
			$filename = $matches[1];
			file_put_contents($export_run_dir . '/' . $filename, $response->getBody());

			ilUtil::zip($export_run_dir, $export_directory . '/' . $new_file);
			ilUtil::delDir($export_run_dir);

			include_once 'Services/Export/classes/class.ilExportFileInfo.php';
			$exp = new ilExportFileInfo($this->object->getId());
			$exp->setVersion(ILIAS_VERSION_NUMERIC);
			$exp->setCreationDate(new ilDateTime($ts, IL_CAL_UNIX));
			$exp->setExportType($type);
			$exp->setFilename($new_file);
			$exp->create();
		}
		catch(Exception $e)
		{
			//@todo: Handle Exception
			$ilLog->write($e->getMessage());
			$ilLog->logStack();
			ilUtil::sendFailure($e->getMessage(), true);
		}
	}

	/**
	 * @param ilExportGUI $exportgui
	 */
	protected function addSupportedExportFormats(ilExportGUI $exportgui)
	{
		switch($this->object->getDocType())
		{
			case self::DOC_TYPE_DOCUMENT:
				$exportgui->addFormat('pdf', $this->plugin->txt('exp_pdf'), $this, 'exportToPdf');
				$exportgui->addFormat('html', $this->plugin->txt('exp_html'), $this, 'exportToHtml');
				$exportgui->addFormat('odt', $this->plugin->txt('exp_odt'), $this, 'exportToOdt');
				$exportgui->addFormat('docx', $this->plugin->txt('exp_docx'), $this, 'exportToDocx');
				$exportgui->addFormat('doc', $this->plugin->txt('exp_doc'), $this, 'exportToDoc');
				$exportgui->addFormat('rtf', $this->plugin->txt('exp_rtf'), $this, 'exportToRtf');
				$exportgui->addFormat('txt', $this->plugin->txt('exp_txt'), $this, 'exportToTxt');
				$exportgui->addFormat('zip', $this->plugin->txt('exp_zip'), $this, 'exportToZip');
				$exportgui->addFormat('png', $this->plugin->txt('exp_png'), $this, 'exportToPng');
				break;

			case self::DOC_TYPE_SPREADSHEET:
				$exportgui->addFormat('pdf', $this->plugin->txt('exp_pdf'), $this, 'exportToPdf');
				$exportgui->addFormat('ods', $this->plugin->txt('exp_ods'), $this, 'exportToOds');
				$exportgui->addFormat('xlsx', $this->plugin->txt('exp_xlsx'), $this, 'exportToXlsx');
				$exportgui->addFormat('xls', $this->plugin->txt('exp_xls'), $this, 'exportToXls');
				$exportgui->addFormat('csv', $this->plugin->txt('exp_csv'), $this, 'exportToCsv');
				$exportgui->addFormat('tsv', $this->plugin->txt('exp_tsv'), $this, 'exportToTsv');
				$exportgui->addFormat('html', $this->plugin->txt('exp_html'), $this, 'exportToHtml');
				break;

			case self::DOC_TYPE_PRESENTATION:
				$exportgui->addFormat('txt', $this->plugin->txt('exp_txt'), $this, 'exportToTxt');
				$exportgui->addFormat('svg', $this->plugin->txt('exp_svg'), $this, 'exportToSvg');
				$exportgui->addFormat('pdf', $this->plugin->txt('exp_pdf'), $this, 'exportToPdf');
				$exportgui->addFormat('pptx', $this->plugin->txt('exp_pptx'), $this, 'exportToPptx');
				$exportgui->addFormat('png', $this->plugin->txt('exp_png'), $this, 'exportToPng');
				$exportgui->addFormat('jpeg', $this->plugin->txt('exp_jpeg'), $this, 'exportToJpeg');
				break;

			default:
				throw new InvalidArgumentException("Document type {$this->object->getDocType()} not supported");
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
		 * @var $ilUser   ilObjUser
		 */
		global $ilTabs, $ilCtrl, $ilAccess, $ilUser;

		if($ilAccess->checkAccess('read', '', $this->object->getRefId()))
		{
			$ilTabs->addTab('content', $this->txt('content'), $ilCtrl->getLinkTarget($this, 'showContent'));
		}

		$this->addInfoTab();

		$participant = ilGoogleDocsParticipant::getInstanceByObjId($this->object->getId(), $ilUser->getId());
		if($participant->isAssigned())
		{
			$ilTabs->addTab('personal_settings', $this->txt('personal_settings'), $ilCtrl->getLinkTarget($this, 'editPersonalSettings'));
		}

		if($ilAccess->checkAccess('write', '', $this->object->getRefId()))
		{
			$ilTabs->addTab('properties', $this->txt('properties'), $ilCtrl->getLinkTarget($this, 'editProperties'));
			$ilTabs->addTab('members', $this->txt('members'), $this->ctrl->getLinkTarget($this, 'editParticipants'));
			$ilTabs->addTarget('meta_data', $this->ctrl->getLinkTargetByClass('ilmdeditorgui', ''), '', 'ilmdeditorgui');
		}
		else if($ilAccess->checkAccess('read', '', $this->object->getRefId()))
		{
			$ilTabs->addTab('members', $this->txt('members'), $this->ctrl->getLinkTarget($this, 'showParticipantsGallery'));
		}

		if($ilAccess->checkAccess('write', '', $this->object->getRefId()))
		{
			$ilTabs->addTarget(
				'export',
				$this->ctrl->getLinkTargetByClass('ilexportgui', ''),
				'export',
				'ilexportgui'
			);
		}

		$this->addPermissionTab();
	}

	/**
	 * @param string $a_tab
	 */
	protected function setSubTabs($a_tab)
	{
		/**
		 * @var $ilTabs   ilTabsGUI
		 * @var $ilCtrl   ilCtrl
		 * @var $ilAccess ilAccessHandler
		 */
		global $ilTabs, $ilCtrl, $ilAccess;

		switch($a_tab)
		{
			case 'members':
				if($ilAccess->checkAccess('write', '', $this->object->getRefId()))
				{
					$ilTabs->addSubTabTarget($this->plugin->txt('participant_administration'), $ilCtrl->getLinkTarget($this, 'editParticipants'), array('showSearch', 'handleMultiCommand', 'storedUserList', 'listUsers', 'showSearchResults', 'performSearch', 'start', 'editParticipants', 'sendMailToSelectedUsers', 'deleteParticipants', 'confirmDeleteParticipants', 'addParticipants'), '', '', false, true);
				}
				if($ilAccess->checkAccess('read', '', $this->object->getRefId()))
				{
					$ilTabs->addSubTabTarget($this->plugin->txt('participant_gallery'), $ilCtrl->getLinkTarget($this, 'showParticipantsGallery'), array('showParticipantsGallery'), '', '', false, true);
				}
				break;
		}
	}

	/**
	 * @param string $type
	 * @return array
	 */
	protected function initCreationForms($type)
	{
		return array(
			self::CFORM_NEW => $this->initCreateForm($type)
		);
	}

	/**
	 * @param string $type
	 * @return ilPropertyFormGUI
	 */
	public function  initCreateForm($type)
	{
		$form = parent::initCreateForm($type);

		$creation_type = new ilRadioGroupInputGUI($this->plugin->txt('creation_type'), 'creation_type');
		$creation_type->setRequired(true);
		$creation_type->setValue(self::CREATION_TYPE_NEW);
		$action_new    = new ilRadioOption($this->plugin->txt('creation_type_new'), self::CREATION_TYPE_NEW);
		$action_upload = new ilRadioOption($this->plugin->txt('creation_type_upload'), self::CREATION_TYPE_UPLOAD);
		$creation_type->addOption($action_new);
		$creation_type->addOption($action_upload);

		$document_type = new ilRadioGroupInputGUI($this->plugin->txt('doctype'), 'doc_type');
		$document_type->setRequired(true);
		$document_type->setValue(self::DOC_TYPE_DOCUMENT);
		$type_doc          = new ilRadioOption($this->plugin->txt('doctype_doc'), self::DOC_TYPE_DOCUMENT);
		$type_spreadsheet  = new ilRadioOption($this->plugin->txt('doctype_xls'), self::DOC_TYPE_SPREADSHEET);
		$type_presentation = new ilRadioOption($this->plugin->txt('doctype_ppt'), self::DOC_TYPE_PRESENTATION);
		$document_type->addOption($type_doc);
		$document_type->addOption($type_spreadsheet);
		$document_type->addOption($type_presentation);

		$upload_field = new ilFileInputGUI($this->plugin->txt('gdocs_file'), 'gdocs_file');
		$class        = new ReflectionClass('Zend_Gdata_Docs');
		$property     = $class->getProperty('SUPPORTED_FILETYPES');
		$property->setAccessible(true);
		$suffixes = array_map('strtolower', array_keys($property->getValue()));
		sort($suffixes);
		$upload_field->setSuffixes($suffixes);
		$upload_field->setRequired(true);
		$action_upload->addSubItem($upload_field);

		$google_account = new ilGoogleAccountInputGUI($this->plugin->txt('google_account'), 'google_account');
		$google_account->setInfo($this->plugin->txt('google_account_owner_info'));
		$google_account->setRequired(true);

		$action_new->addSubItem($document_type);
		
		$form->addItem($creation_type);
		$form->addItem($google_account);

		return $form;
	}

	/**
	 * @return ilPropertyFormGUI
	 */
	protected function getPersonalSettingsInputForm()
	{
		/**
		 * @var $ilCtrl ilCtrl
		 */
		global $ilCtrl;

		if($this->personal_settings_form instanceof ilPropertyFormGUI)
		{
			return $this->personal_settings_form;
		}

		$this->personal_settings_form = new ilPropertyFormGUI();
		$this->personal_settings_form->setTitle($this->plugin->txt('personal_settings'));
		$this->personal_settings_form->setFormAction($ilCtrl->getFormAction($this, 'updatePersonalSettings'));

		$google_account = new ilGoogleAccountInputGUI($this->plugin->txt('google_account'), 'google_account');
		$google_account->setInfo($this->plugin->txt('google_account_participant_info'));
		$google_account->setRequired(true);

		$this->personal_settings_form->addItem($google_account);

		$this->personal_settings_form->addCommandButton('updatePersonalSettings', $this->txt('save'));

		return $this->personal_settings_form;
	}

	/**
	 *
	 */
	protected function updatePersonalSettings()
	{
		/**
		 * @var $tpl    ilTemplate
		 * @var $lng    ilLanguage
		 * @var $ilCtrl ilCtrl
		 * @var $ilUser ilObjUser
		 * @var $ilTabs ilTabsGUI
		 */
		global $tpl, $lng, $ilCtrl, $ilUser, $ilTabs;

		$ilTabs->activateTab('personal_settings');

		$form = $this->getPersonalSettingsInputForm();
		if($form->checkInput())
		{
			$participant = ilGoogleDocsParticipant::getInstanceByObjId($this->object->getId(), $ilUser->getId());
			$participant->updateGoogleAccount($form->getInput('google_account'));
			ilUtil::sendSuccess($lng->txt('saved_successfully'), true);
			$ilCtrl->redirect($this, 'editPersonalSettings');
		}

		$form->setValuesByPost();
		$tpl->setContent($form->getHTML());
	}

	/**
	 *
	 */
	protected function showContent()
	{
		/**
		 * @var $tpl    ilTemplate
		 * @var $ilTabs ilTabsGUI
		 * @var $lng    ilLanguage
		 * @var $ilUser ilObjUser
		 * @var $ilLog  ilLog
		 */
		global $tpl, $ilTabs, $lng, $ilUser, $ilLog;

		$ilTabs->activateTab('content');

		require_once 'Services/jQuery/classes/class.iljQueryUtil.php';
		iljQueryUtil::initjQuery();
		iljQueryUtil::initjQueryUI();

		$tpl->addCss("./Customizing/global/plugins/Services/Repository/RepositoryObject/GoogleDocs/templates/jquery-ui-1.9.0.custom.min.css");
		$tpl->addCss("./Customizing/global/plugins/Services/Repository/RepositoryObject/GoogleDocs/templates/gdocs.css");

		$participant = ilGoogleDocsParticipant::getInstanceByObjId($this->object->getId(), $ilUser->getId());
		if($this->object->hasToSubmitGoogleAccount($participant))
		{
			ilUtil::sendInfo($this->txt('google_account_participant_desc'), true);
			$this->ctrl->redirect($this, 'editPersonalSettings');
		}
		else if($participant->isAssigned())
		{
			try
			{
				$this->object->grantAclPermissions($participant);
			}
			catch(Exception $e)
			{
				//@todo: Handle Exception
				$ilLog->write($e->getMessage());
				$ilLog->logStack();
				ilUtil::sendFailure($e->getMessage());
			}

			$form = new ilPropertyFormGUI();
			$url  = new ilCustomInputGUI($lng->txt('url'), 'edit_url');
			$href = '<a href="' . $this->object->getEditDocUrl() . '" target="_blank" >' . $this->object->getEditDocUrl() . '</a>';
			$url->setHtml($href);
			$form->addItem($url);
			$html = $form->getHTML();
			$tpl->addJavaScript("./Customizing/global/plugins/Services/Repository/RepositoryObject/GoogleDocs/templates/gdocs.js");
			$content_tpl = $this->plugin->getTemplate('tpl.content.html');

			$content_tpl->setVariable('URL', $this->object->getEditDocUrl());

			$tpl->setPermanentLink($this->object->getType(), $this->object->getRefId());
			$tpl->setContent($html . $content_tpl->get());
		}
		else
		{
			ilUtil::sendInfo($this->txt('membership_required_for_content'), true);
		}
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

		if($this->object->getDocType() == self::DOC_TYPE_DOCUMENT)
		{
			$doc_type = $this->plugin->txt('doctype_doc');
		}
		else if($this->object->getDocType() == self::DOC_TYPE_SPREADSHEET)
		{
			$doc_type = $this->plugin->txt('doctype_xls');
		}
		else if($this->object->getDocType() == self::DOC_TYPE_PRESENTATION)
		{
			$doc_type = $this->plugin->txt('doctype_ppt');
		}
		else
		{
			$doc_type = $this->plugin->txt('doctype_not_valid');
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

	/**
	 *
	 */
	public function editParticipants()
	{
		/**
		 * @var $ilTabs     ilTabsGUI
		 * @var $tpl        ilTemplate
		 * @var $rbacreview ilRbacReview
		 * @var $ilUser     ilObjUser
		 */
		global $ilTabs, $tpl, $rbacreview, $ilUser;

		$ilTabs->activateTab('members');

		$this->setSubTabs('members');

		/**
		 * @var $participants_tpl ilTemplate
		 */
		$participants_tpl = $this->plugin->getTemplate('tpl.participants.html');

		$this->addSearchToolbar();

		$this->setShowHidePrefs();

		$print_actions = false;

		if($rbacreview->assignedUsers((int)$this->object->getDefaultWriterRole()))
		{
			if($ilUser->getPref('il_xgdo_writer_hide'))
			{
				$table = new ilGoogleDocsParticipantsTableGUI($this, 'editParticipants', self::GDOC_WRITER, false);
				$this->ctrl->setParameter($this, 'writer_hide', 0);
				$table->addHeaderCommand(
					$this->ctrl->getLinkTarget($this, 'editParticipants'),
					$this->lng->txt('show'),
					'',
					ilUtil::getImagePath('edit_add.png')
				);
				$this->ctrl->clearParameters($this);
			}
			else
			{
				$table = new ilGoogleDocsParticipantsTableGUI($this, 'editParticipants', self::GDOC_WRITER, true);
				$this->ctrl->setParameter($this, 'writer_hide', 1);
				$table->addHeaderCommand(
					$this->ctrl->getLinkTarget($this, 'editParticipants'),
					$this->lng->txt('hide'),
					'',
					ilUtil::getImagePath('edit_remove.png')
				);
				$this->ctrl->clearParameters($this);
				$print_actions = true;
			}
			$table->setTitle(
				$this->plugin->txt('writers'),
				'icon_usr.gif',
				$this->plugin->txt('writers')
			);
			$table->parse($rbacreview->assignedUsers((int)$this->object->getDefaultWriterRole()));
			$participants_tpl->setVariable('WRITERS', $table->getHTML());
		}

		if($rbacreview->assignedUsers((int)$this->object->getDefaultReaderRole()))
		{
			if($ilUser->getPref('il_xgdo_reader_hide'))
			{
				$table = new ilGoogleDocsParticipantsTableGUI($this, 'editParticipants', self::GDOC_READER, false);
				$this->ctrl->setParameter($this, 'reader_hide', 0);
				$table->addHeaderCommand(
					$this->ctrl->getLinkTarget($this, 'editParticipants'),
					$this->lng->txt('show'),
					'',
					ilUtil::getImagePath('edit_add.png')
				);
				$this->ctrl->clearParameters($this);
			}
			else
			{
				$table = new ilGoogleDocsParticipantsTableGUI($this, 'editParticipants', self::GDOC_READER, true);
				$this->ctrl->setParameter($this, 'reader_hide', 1);
				$table->addHeaderCommand(
					$this->ctrl->getLinkTarget($this, 'editParticipants'),
					$this->lng->txt('hide'),
					'',
					ilUtil::getImagePath('edit_remove.png')
				);
				$this->ctrl->clearParameters($this);
				$print_actions = true;
			}
			$table->setTitle(
				$this->plugin->txt('readers'),
				'icon_usr.gif',
				$this->plugin->txt('readers')
			);
			$table->parse($rbacreview->assignedUsers((int)$this->object->getDefaultReaderRole()));
			$participants_tpl->setVariable('READERS', $table->getHTML());
		}

		$participants_tpl->setVariable('FORMACTION', $this->ctrl->getFormAction($this, 'editParticipants'));

		if($print_actions)
		{
			$participants_tpl->setVariable('BTN_FOOTER_VAL', $this->lng->txt('remove'));
			$participants_tpl->setVariable('BTN_FOOTER_MAIL', $this->plugin->txt('send_mail'));
			$participants_tpl->setVariable('ARROW_DOWN', ilUtil::getImagePath('arrow_downright.png'));
		}

		$tpl->setContent($participants_tpl->get());
	}

	/**
	 *
	 */
	protected function addSearchToolbar()
	{
		/**
		 * @var $ilToolbar ilToolbarGUI
		 */
		global $ilToolbar;

		$types = array(
			self::GDOC_READER => $this->plugin->txt('reader_short_add'),
			self::GDOC_WRITER => $this->plugin->txt('writer_short_add')
		);
		require_once 'Services/Search/classes/class.ilRepositorySearchGUI.php';
		ilRepositorySearchGUI::fillAutoCompleteToolbar(
			$this,
			$ilToolbar,
			array(
				'auto_complete_name' => $this->lng->txt('user'),
				'user_type'          => $types,
				'submit_name'        => $this->lng->txt('add')
			)
		);

		$ilToolbar->addSeparator();

		$ilToolbar->addButton(
			$this->plugin->txt('search_users'),
			$this->ctrl->getLinkTargetByClass('ilRepositorySearchGUI', 'start')
		);
	}

	/**
	 *
	 */
	protected function setShowHidePrefs()
	{
		/**
		 * @var $ilUser ilObjUser
		 */
		global $ilUser;

		if(isset($_GET['writer_hide']))
		{
			$ilUser->writePref('il_xgdo_writer_hide', (int)$_GET['writer_hide']);
		}

		if(isset($_GET['reader_hide']))
		{
			$ilUser->writePref('il_xgdo_reader_hide', (int)$_GET['reader_hide']);
		}
	}

	/**
	 * @param array  $user_ids
	 * @param string $type
	 */
	public function addParticipants(array $user_ids, $type)
	{
		if(!$user_ids)
		{
			ilUtil::sendFailure($this->lng->txt('select_one'), true);
			return false;
		}

		$added_users = array();
		try
		{
			$participants = ilGoogleDocsParticipants::getInstanceByObjId($this->object->getId());
			foreach((array)$user_ids as $usr_id)
			{
				if(!ilObjUser::_lookupLogin($usr_id))
				{
					continue;
				}

				switch($type)
				{
					case self::GDOC_WRITER:
						if($participants->add($usr_id, self::GDOC_WRITER))
						{
							$added_users[] = $usr_id;
						}
						break;

					case self::GDOC_READER:
						if($participants->add($usr_id, self::GDOC_READER))
						{
							$added_users[] = $usr_id;
						}
						break;

					default:
						throw new InvalidArgumentException("Invalid role type {$type} given");
						break;
				}
			}
		}
		catch(Exception $e)
		{
			ilUtil::sendFailure($e->getMessage(), true);
			return false;
		}

		if($added_users)
		{
			ilUtil::sendSuccess($this->plugin->txt('assigned_users' . (count($added_users) == 1 ? '_s' : '_p')), true);
		}
		else
		{
			ilUtil::sendFailure($this->plugin->txt('no_users_assigned' . (count($user_ids) == 1 ? '_s' : '_p')), true);
		}

		$this->ctrl->redirect($this, 'editParticipants');
	}

	/**
	 *
	 */
	protected function deleteParticipants()
	{
		if(!isset($_POST['participants']) || !is_array($_POST['participants']) || !count($_POST['participants']))
		{
			ilUtil::sendFailure($this->lng->txt('select_one'));
			$this->editParticipants();
			return;
		}

		$participants = ilGoogleDocsParticipants::getInstanceByObjId($this->object->getId());
		foreach((array)$_POST['participants'] as $usr_id)
		{
			try
			{
				if($participants->hasParticipantByIdGoogleAccount($usr_id))
				{
					$this->object->revokeAclPermissions($participants->getGoogleAccountById($usr_id));
				}
				$participants->delete($usr_id);
			}
			catch(Exception $e)
			{
				// @todo: Exception handling
			}
		}

		ilUtil::sendSuccess($this->plugin->txt('participants_removed' . (count($_POST['participants']) == 1 ? '_s' : '_p')));
		$this->editParticipants();
	}

	/**
	 *
	 */
	protected function confirmDeleteParticipants()
	{
		/**
		 * @var $ilTabs ilTabsGUI
		 * @var $tpl    ilTemplate
		 */
		global $ilTabs, $tpl;

		if(!isset($_POST['writers']) && !isset($_POST['readers']))
		{
			ilUtil::sendFailure($this->lng->txt('select_one'));
			$this->editParticipants();
			return;
		}

		$ilTabs->activateTab('participants');

		$participants_to_delete = (array)array_unique(array_merge((array)$_POST['writers'], (array)$_POST['readers']));
		if(!count($participants_to_delete))
		{
			ilUtil::sendFailure($this->lng->txt('select_one'));
			$this->editParticipants();
			return false;
		}

		require_once 'Services/Utilities/classes/class.ilConfirmationGUI.php';
		require_once 'Services/User/classes/class.ilUserUtil.php';
		$confirmation_gui = new ilConfirmationGUI();
		$confirmation_gui->setFormAction($this->ctrl->getFormAction($this, 'deleteParticipants'));
		$confirmation_gui->setConfirm($this->lng->txt('confirm'), 'deleteParticipants');
		$confirmation_gui->setCancel($this->lng->txt('cancel'), 'editParticipants');

		foreach($participants_to_delete as $usr_id)
		{
			$confirmation_gui->addItem('participants[]',
				$usr_id,
				ilUserUtil::getNamePresentation($usr_id),
				ilUtil::getImagePath('icon_usr.png')
			);
		}

		$confirmation_gui->setHeaderText($this->plugin->txt('remove_participants_info' . (count($participants_to_delete) == 1 ? '_s' : '_p')));

		$tpl->setContent($confirmation_gui->getHTML());
	}

	/**
	 *
	 */
	protected function sendMailToSelectedUsers()
	{
		if(!isset($_POST['writers']) && !isset($_POST['readers']))
		{
			ilUtil::sendFailure($this->lng->txt('select_one'));
			$this->editParticipants();
			return;
		}

		$_POST['participants'] = array_unique(array_merge((array)$_POST['writers'], (array)$_POST['readers']));
		if(!count($_POST['participants']))
		{
			ilUtil::sendFailure($this->lng->txt('select_one'));
			$this->editParticipants();
			return;
		}

		$rcps = array();
		foreach($_POST['participants'] as $usr_id)
		{
			$login = ilObjUser::_lookupLogin((int)$usr_id);
			if(strlen($login))
			{
				$rcps[] = $login;
			}
		}

		if(!$rcps)
		{
			ilUtil::sendFailure($this->lng->txt('select_one'));
			$this->editParticipants();
		}

		require_once 'Services/Mail/classes/class.ilMailFormCall.php';
		ilUtil::redirect(
			ilMailFormCall::getRedirectTarget(
				$this,
				'editParticipants',
				array(),
				array(
					'type'   => 'new',
					'rcp_to' => implode(',', $rcps)
				)
			)
		);
	}

	/**
	 *
	 */
	protected function showParticipantsGallery()
	{
		/**
		 * @var $ilTabs     ilTabsGUI
		 * @var $tpl        ilTemplate
		 * @var $rbacreview ilRbacReview
		 * @var $ilUser     ilObjUser
		 */
		global $ilTabs, $tpl, $rbacreview, $ilUser;

		$ilTabs->activateTab('members');

		$this->setSubTabs('members');

		/**
		 * @var $participants_tpl ilTemplate
		 */
		$participants_tpl = $this->plugin->getTemplate('tpl.members_gallery.html');

		require_once 'Services/User/classes/class.ilUserQuery.php';
		$usr_data = ilUserQuery::getUserListData(
			'login',
			'ASC',
			0,
			9999,
			'',
			'',
			null,
			false,
			false,
			0,
			0,
			null,
			array(),
			array_unique(array_merge($rbacreview->assignedUsers($this->object->getDefaultReaderRole()), $rbacreview->assignedUsers($this->object->getDefaultWriterRole())))
		);

		if(count($usr_data['set']))
		{
			foreach($usr_data['set'] as $member)
			{
				/**
				 * @var $user ilObjUser
				 */
				if(!($user = ilObjectFactory::getInstanceByObjId($member['usr_id'], false)))
				{
					continue;
				}

				if(!$user->getActive())
				{
					continue;
				}

				$pp = $user->getPref('public_profile') == 'g' || ($user->getPref('public_profile') == 'y' && $ilUser->getId() != ANONYMOUS_USER_ID);
				$this->ctrl->setParameterByClass('ilpublicuserprofilegui', 'user', $user->getId());
				$profile_target = $this->ctrl->getLinkTargetByClass('ilpublicuserprofilegui', 'getHTML');

				if($pp)
				{
					$participants_tpl->setCurrentBlock('member_linked');
					$participants_tpl->setVariable('LINK_PROFILE', $profile_target);
					$participants_tpl->setVariable('NAME', ilUserUtil::getNamePresentation($user->getId()));
					$participants_tpl->parseCurrentBlock();
				}
				else
				{
					$participants_tpl->setCurrentBlock('member_not_linked');
					$participants_tpl->setVariable('NAME', ilUserUtil::getNamePresentation($user->getId()));
					$participants_tpl->parseCurrentBlock();
				}

				$participants_tpl->setCurrentBlock('members');
				if($pp && $user->getPref('public_upload') == 'y')
				{
					$participants_tpl->setVariable('SRC_USR_IMAGE', $user->getPersonalPicturePath('xsmall'));
				}
				$participants_tpl->parseCurrentBlock();
			}
		}

		$tpl->setContent($participants_tpl->get());
	}

	/**
	 * Overwriting this method is necessary to handle creation problems with the api
	 */
	public function save()
	{
		$this->saveObject();
	}

	/**
	 * Overwriting this method is necessary to handle creation problems with the api
	 */
	public function saveObject()
	{
		/**
		 * @var $ilCtrl ilCtrl
		 */
		global $ilCtrl;

		try
		{
			parent::saveObject();
		}
		catch(Exception $e)
		{
			if($this->plugin->txt($e->getMessage()) != '-' . $e->getMessage() . '-')
			{
				ilUtil::sendFailure($this->plugin->txt($e->getMessage()), true);
			}

			$ilCtrl->setParameterByClass('ilrepositorygui', 'ref_id', (int)$_GET['ref_id']);
			$ilCtrl->redirectByClass('ilrepositorygui');
		}
	}

	/**
	 * @param ilObjGoogleDocs $newObj
	 */
	public function afterSave(ilObjGoogleDocs $newObj)
	{
		/**
		 * @var $ilUser ilObjUser
		 * @var $ilLog  ilLog
		 */
		global $ilUser, $ilLog;

		try
		{
			$participant = ilGoogleDocsParticipant::getInstanceByObjId($newObj->getId(), $ilUser->getId());
			$participant->add(self::GDOC_WRITER);
			$participant->updateGoogleAccount(ilUtil::stripSlashes($_POST['google_account']));
			$newObj->grantAclPermissions($participant);
		}
		catch(Exception $e)
		{
			//@todo: Handle Exception
			$ilLog->write($e->getMessage());
			$ilLog->logStack();
			ilUtil::sendFailure($e->getMessage(), true);
		}

		parent::afterSave($newObj);
	}
	
	public function editPersonalSettings()
	{
		/**
		 * @var $ilUser ilObjUser
		 * @var $tpl    ilTemplate
		 * @var $ilTabs ilTabsGUI
		 */
		global $ilUser, $tpl, $ilTabs;

		$ilTabs->activateTab('personal_settings');

		$participant = ilGoogleDocsParticipant::getInstanceByObjId($this->object->getId(), $ilUser->getId());
		if(!$participant->isAssigned())
		{
			$this->ctrl->redirect($this, 'showContent');
		}

		$form = $this->getPersonalSettingsInputForm();
		$form->setValuesByArray(array(
			'google_account' => $participant->getGoogleAccount()
		));
		$tpl->setContent($form->getHTML());
	}

	/**
	 * @param string $a_sub_type
	 * @param int    $a_sub_id
	 * @return ilObjectListGUI|ilObjGoogleDocsListGUI
	 */
	protected function initHeaderAction($a_sub_type = null, $a_sub_id = null)
	{
		/**
		 * @var $ilUser ilObjUser
		 */
		global $ilUser;

		$lg = parent::initHeaderAction();
		if($lg instanceof ilObjGoogleDocsListGUI)
		{
			if($ilUser->getId() != ANONYMOUS_USER_ID)
			{
				// Maybe handle notifications in future ...
			}
		}

		return $lg;
	}

	/**
	 * @see ilDesktopItemHandling::addToDesk()
	 */
	public function addToDeskObject()
	{
		/**
		 * @var $ilSetting ilSetting
		 * @var $lng       ilLanguage
		 */
		global $ilSetting, $lng;

		if((int)$ilSetting->get('disable_my_offers'))
		{
			$this->showContent();
			return;
		}

		include_once './Services/PersonalDesktop/classes/class.ilDesktopItemGUI.php';
		ilDesktopItemGUI::addToDesktop();
		ilUtil::sendSuccess($lng->txt('added_to_desktop'));
		$this->showContent();
	}

	/**
	 * @see ilDesktopItemHandling::removeFromDesk()
	 */
	public function removeFromDeskObject()
	{
		global $ilSetting, $lng;

		if((int)$ilSetting->get('disable_my_offers'))
		{
			$this->showContent();
			return;
		}

		include_once './Services/PersonalDesktop/classes/class.ilDesktopItemGUI.php';
		ilDesktopItemGUI::removeFromDesktop();
		ilUtil::sendSuccess($lng->txt('removed_from_desktop'));
		$this->showContent();
	}
}