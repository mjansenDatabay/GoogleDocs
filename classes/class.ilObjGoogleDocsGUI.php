<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPluginGUI.php';
require_once 'Services/Form/classes/class.ilPropertyFormGUI.php';
require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';
require_once dirname(__FILE__) . '/../classes/class.ilGoogleDocsParticipantsTableGUI.php';
require_once 'Services/PersonalDesktop/interfaces/interface.ilDesktopItemHandling.php';

/**
 * @ilCtrl_isCalledBy ilObjGoogleDocsGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
 * @ilCtrl_Calls      ilObjGoogleDocsGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilRepositorySearchGUI, ilPublicUserProfileGUI, ilCommonActionDispatcherGUI
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
		/**
		 * @var $ilTabs ilTabsGUI
		 */
		global $ilTabs;

		$next_class = $this->ctrl->getNextClass($this);
		switch($next_class)
		{
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

					case 'showParticipantsGallery':
					case 'showContent':
						$this->checkPermission('read');
						$this->$cmd();
						break;
				}
				break;
		}

		$this->addHeaderAction();
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

		if($ilAccess->checkAccess('write', '', $this->object->getRefId()))
		{
			$ilTabs->addTab('members', $this->txt('members'), $this->ctrl->getLinkTarget($this, 'editParticipants'));
		}
		else if($ilAccess->checkAccess('read', '', $this->object->getRefId()))
		{
			$ilTabs->addTab('members', $this->txt('members'), $this->ctrl->getLinkTarget($this, 'showParticipantsGallery'));
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

		$radio_doctype = new ilRadioGroupInputGUI($this->plugin->txt('doctype'), 'doc_type');
		$radio_doctype->setRequired(true);
		$option_1 = new ilRadioOption($this->plugin->txt('doctype_doc'), self::GOOGLE_DOC);
		$option_2 = new ilRadioOption($this->plugin->txt('doctype_xls'), self::GOOGLE_XLS);
		$option_3 = new ilRadioOption($this->plugin->txt('doctype_ppt'), self::GOOGLE_PPT);

		$radio_doctype->addOption($option_1);
		$radio_doctype->addOption($option_2);
		$radio_doctype->addOption($option_3);

		$radio_doctype->setValue(self::GOOGLE_DOC);

		$google_account = new ilTextInputGUI($this->plugin->txt('google_account'), 'google_account');
		$google_account->setInfo($this->plugin->txt('google_account_info'));
		$google_account->setRequired(true);

		$form->addItem($radio_doctype);
		$form->addItem($google_account);

		return $form;
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
		 */
		global $tpl, $ilTabs, $lng;

		$this->plugin->includeClass('class.ilGoogleDocsAPI.php');

		$ilTabs->activateTab('content');

		require_once 'Services/jQuery/classes/class.iljQueryUtil.php';
		iljQueryUtil::initjQuery();
		iljQueryUtil::initjQueryUI();

		$tpl->addCss("./Customizing/global/plugins/Services/Repository/RepositoryObject/GoogleDocs/templates/jquery-ui-1.9.0.custom.min.css");
		$tpl->addCss("./Customizing/global/plugins/Services/Repository/RepositoryObject/GoogleDocs/templates/gdocs.css");

		// @todo: Check whether the user is assigned to either a local reader or a local writer role. If we did no store the users' google account, yet, we have to force him to enter the account name

		$form = new ilPropertyFormGUI();

		$url  = new ilCustomInputGUI($lng->txt('url'), 'edit_url');
		$href = '<a href="' . $this->object->getEditDocUrl() . '" target="_blank" >' . $this->object->getEditDocUrl() . '</a>';
		$url->setHtml($href);

		$form->addItem($url);

		$html = $form->getHTML();

		$tpl->addJavaScript("./Customizing/global/plugins/Services/Repository/RepositoryObject/GoogleDocs/templates/gdocs.js");

		$content_tpl = new ilTemplate($this->plugin->getDirectory() . '/templates/tpl.content.html', false, false);
		$content_tpl->setVariable('URL', $this->object->getEditDocUrl());

		$tpl->setContent($html . $content_tpl->get());
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

		if($this->object->getDocType() == self::GOOGLE_DOC)
		{
			$doc_type = $this->plugin->txt('doctype_doc');
		}
		else if($this->object->getDocType() == self::GOOGLE_XLS)
		{
			$doc_type = $this->plugin->txt('doctype_xls');
		}
		else if($this->object->getDocType() == self::GOOGLE_PPT)
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
	 * @param array  $a_user_ids
	 * @param string $a_type
	 */
	public function addParticipants(array $a_user_ids, $a_type)
	{
		if(!$a_user_ids)
		{
			ilUtil::sendFailure($this->lng->txt('select_one'));
			return;
		}
		
		try
		{
			// @todo: Check already assigned members, if no user has to be assigned, send a failure message
			$this->object->addParticipants($a_user_ids, $a_type);
		}
		catch(Exception $e)
		{
			ilUtil::sendFailure($e->getMessage());
			return false;
		}

		ilUtil::sendSuccess($this->plugin->txt('assigned_users'.(count($a_user_ids) == 1 ? '_s' : '_p')), true);
		$this->ctrl->redirect($this, 'editParticipants');
	}

	/**
	 *
	 */
	protected function deleteParticipants()
	{
		/**
		 * @var $rbacadmin ilRbacAdmin
		 */
		global $rbacadmin;

		if(!isset($_POST['participants']) || !is_array($_POST['participants']) || !count($_POST['participants']))
		{
			ilUtil::sendFailure($this->lng->txt('select_one'));
			$this->editParticipants();
			return;
		}

		foreach((array)$_POST['participants'] as $usr_id)
		{
			$rbacadmin->deassignUser($this->object->getDefaultReaderRole(), $usr_id);
			$rbacadmin->deassignUser($this->object->getDefaultWriterRole(), $usr_id);
			// @todo: Revoke permission via api
		}

		ilUtil::sendSuccess($this->plugin->txt('participants_removed'.(count($_POST['participants']) == 1 ? '_s' : '_p')));
		$this->editParticipants();
	}

	/**
	 * @return bool
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

		$confirmation_gui->setHeaderText($this->plugin->txt('remove_participants_info'.(count($participants_to_delete) == 1 ? '_s' : '_p')));

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
			$rcps[] = ilObjUser::_lookupLogin((int)$usr_id);
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
			ilUtil::sendFailure($this->plugin->txt($e->getMessage()), true);

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
		 */
		global $ilUser;

		$newObj->addParticipants(array($ilUser->getId()), self::GDOC_WRITER);

		parent::afterSave($newObj);
	}

	/**
	 * @param string $a_sub_type
	 * @param int $a_sub_id
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
		 * @var $lng ilLanguage
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