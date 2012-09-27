<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPluginListGUI.php';

class ilObjGoogleDocsListGUI extends ilObjectPluginListGUI
{
	/**
	 * @return string
	 */
	public function getGuiClass()
	{
		return 'ilObjGoogleDocsGUI';
	}

	/**
	 * @return array
	 */
	public function initCommands()
	{
		return array
		(
			array(
				'permission' => 'read',
				'cmd'        => 'infoScreen',
				'default'    => true
			),
			array(
				'permission' => 'write',
				'cmd'        => 'editProperties',
				'txt'        => $this->txt('edit'),
				'default'    => false
			),
		);
	}

	/**
 	 *
	 */
	public function initType()
	{
		$this->setType('xgdo');
	}
}
