<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Table/classes/class.ilTable2GUI.php';
require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';
require_once 'Services/User/classes/class.ilUserUtil.php';

/**
 *
 */
class ilGoogleDocsParticipantsTableGUI extends ilTable2GUI implements ilGoogleDocsConstants
{
	/**
	 * @var int
	 */
	protected $type = self::GDOC_WRITER;

	/**
	 * @var bool
	 */
	protected static $export_allowed = false;

	/**
	 * @var bool
	 */
	protected static $confirmation_required = true;

	/*
	 * 
	 */
	protected static $accepted_ids = null;

	/**
	 * @param ilObjGoogleDocsGUI $a_parent_obj
	 * @param string             $a_parent_cmd
	 * @param int|string         $a_type
	 * @param bool               $show_content
	 */
	public function __construct(ilObjGoogleDocsGUI $a_parent_obj, $a_parent_cmd, $a_type = self::GDOC_WRITER, $show_content = true)
	{
		/**
		 * @var $lng    ilLanguage
		 * @var $ilCtrl ilCtrl
		 */
		global $lng, $ilCtrl;

		$this->lng  = $lng;
		$this->ctrl = $ilCtrl;
		$this->type = $a_type;

		$this->setId('xgdo_' . $a_type . '_' . $a_parent_obj->object->getId());

		parent::__construct($a_parent_obj, $a_parent_cmd);

		$this->setFormName('participants');

		$this->addColumn('', 'f', "1");
		$this->addColumn($this->lng->txt('name'), 'lastname', '100%');

		if($this->type == self::GDOC_WRITER)
		{
			$this->setPrefix('writers');
			$this->setSelectAllCheckbox('writers');
		}
		else
		{
			$this->setPrefix('readers');
			$this->setSelectAllCheckbox('readers');
		}

		$this->setDefaultOrderField('lastname');

		$this->setRowTemplate('tpl.participants_row.html', $a_parent_obj->plugin->getDirectory());

		if($show_content)
		{
			$this->enable('sort');
			$this->enable('header');
			$this->enable('numinfo');
			$this->enable('select_all');
		}
		else
		{
			$this->disable('content');
			$this->disable('header');
			$this->disable('footer');
			$this->disable('numinfo');
			$this->disable('select_all');
		}
	}

	public function fillRow(array $data)
	{
		/**
		 * @var $ilAccess ilAccessHandler
		 */
		global $ilAccess;

		if(self::GDOC_WRITER == $this->type)
		{
			$this->tpl->setVariable('VAL_CHECKBOX', ilUtil::formCheckbox(false, 'writers[]', $data['usr_id']));
		}
		else
		{
			$this->tpl->setVariable('VAL_CHECKBOX', ilUtil::formCheckbox(false, 'readers[]', $data['usr_id']));
		}
		
		$this->tpl->setVariable('VAL_NAME', ilUserUtil::getNamePresentation($data['usr_id']));

		if(!$ilAccess->checkAccessOfUser($data['usr_id'], 'read', '', $this->getParentObject()->object->getRefId()) &&
			is_array($info = $ilAccess->getInfo())
		)
		{
			$this->tpl->setCurrentBlock('access_warning');
			$this->tpl->setVariable('VAL_PARENT_ACCESS', $info[0]['text']);
			$this->tpl->parseCurrentBlock();
		}

		if(!ilObjUser::_lookupActive($data['usr_id']))
		{
			$this->tpl->setCurrentBlock('access_warning');
			$this->tpl->setVariable('VAL_PARENT_ACCESS', $this->lng->txt('usr_account_inactive'));
			$this->tpl->parseCurrentBlock();
		}
	}

	/**
	 * @param array $part
	 */
	public function parse(array $part)
	{
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
			$part
		);

		$users = array();
		foreach((array)$usr_data['set'] as $usr)
		{
			$users[] = $usr;
		}
		$this->setData((array)$users);
		return;
	}
}
