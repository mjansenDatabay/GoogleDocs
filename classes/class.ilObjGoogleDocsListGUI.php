<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once 'Services/Repository/classes/class.ilObjectPluginListGUI.php';

/**
 * 
 */
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
		$this->copy_enabled = false;

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
			)
		);
	}

	/**
 	 *
	 */
	public function initType()
	{
		$this->setType('xgdo');
	}

	/**
	 * @param bool   $a_use_asynch
	 * @param bool   $a_get_asynch_commands
	 * @param string $a_asynch_url
	 * @return string
	 */
	public function insertCommands($a_use_asynch = false, $a_get_asynch_commands = false, $a_asynch_url = '')
	{
		/**
		 * @var $ilUser ilObjUser
		 */
		global $ilUser;

		$this->plugin->includeClass('class.ilObjGoogleDocsAccess.php');

		if(
			!ilObjGoogleDocsAccess::_hasReaderRole($ilUser->getId(), $this->ref_id)
			&&
			!ilObjGoogleDocsAccess::_hasWriterRole($ilUser->getId(), $this->ref_id)
		)
		{
			/**
			 * $this->commands is initialized only once. appending the join-button
			 * at this point will produce N buttons for the Nth item
			 */
			$this->commands = array_reverse(
				array_merge(
					$this->initCommands(),
					array(
						array(
							'permission' => 'visible',
							'cmd'        => 'join',
							'txt'        => $this->txt('join'),
							'default'    => false
						)
					)
				)
			);
		}
		else
		{
			$this->commands = $this->initCommands();
		}

		return parent::insertCommands($a_use_asynch, $a_get_asynch_commands, $a_asynch_url);
	}
}
