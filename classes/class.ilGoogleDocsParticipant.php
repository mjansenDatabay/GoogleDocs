<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';

/**
 *
 */
class ilGoogleDocsParticipant implements ilGoogleDocsConstants
{
	/**
	 * @var array
	 */
	protected static $instances = array();

	/**
	 * @var int
	 */
	protected $obj_id = 0;

	/**
	 * @var int
	 */
	protected $usr_id = 0;

	/**
	 * @var string
	 */
	protected $type = '';

	/**
	 * @var int
	 */
	protected $ref_id = 0;

	/**
	 * @var array
	 */
	protected $roles = array();

	/**
	 * @var array
	 */
	protected $participants = array();

	/**
	 * @var array
	 */
	protected $participants_status = array();

	/**
	 * @var array
	 */
	protected $writers = array();

	/**
	 * @var array
	 */
	protected $readers = array();

	/**
	 * @var array
	 */
	protected $role_data = array();

	/**
	 * @var int
	 */
	protected $numReaders = 0;

	/**
	 * @param int $a_obj_id
	 * @param int $a_usr_id
	 */
	protected function __construct($a_obj_id, $a_usr_id)
	{
		$this->obj_id = $a_obj_id;
		$this->usr_id = $a_usr_id;
		$this->type   = ilObject::_lookupType($a_obj_id);
		$ref_ids      = ilObject::_getAllReferences($this->obj_id);
		$this->ref_id = (int)current($ref_ids);

		$this->readParticipant();
		$this->readParticipantStatus();
	}

	/**
	 * @param int $a_obj_id
	 * @param int $a_usr_id
	 * @return ilGoogleDocsParticipant
	 */
	public static function getInstanceByObjId($a_obj_id, $a_usr_id)
	{
		if(isset(self::$instances[$a_obj_id][$a_usr_id]) && self::$instances[$a_obj_id][$a_usr_id] instanceof self)
		{
			return self::$instances[$a_obj_id][$a_usr_id];
		}

		return self::$instances[$a_obj_id][$a_usr_id] = new self($a_obj_id, $a_usr_id);
	}

	/**
	 *
	 */
	protected function readParticipant()
	{
		/**
		 * @var $rbacreview     ilRbacReview
		 * @var $ilObjDataCache ilObjectDataCache
		 */
		global $rbacreview, $ilObjDataCache;

		$rolf = $rbacreview->getRoleFolderOfObject($this->getRefId());
		if(!isset($rolf['ref_id']) or !$rolf['ref_id'])
		{
			return;
		}

		$this->roles  = $rbacreview->getRolesOfRoleFolder($rolf['ref_id'], false);
		$reader_roles = $this->role_data = $this->participants = $this->writers = $this->readers = array();

		foreach($this->roles as $role_id)
		{
			$title = $ilObjDataCache->lookupTitle($role_id);
			switch(substr($title, 0, 14))
			{
				case 'il_xgdo_writer':
					$this->role_data[self::GDOC_WRITER] = $role_id;
					if($rbacreview->isAssigned($this->getUsrId(), $role_id))
					{
						$this->participants[$this->getUsrId()] = true;
						$this->writers[$this->getUsrId()]      = true;
					}
					break;

				case 'il_xgdo_reader':
					$reader_roles[]                     = $role_id;
					$this->role_data[self::GDOC_READER] = $role_id;
					if($rbacreview->isAssigned($this->getUsrId(), $role_id))
					{
						$this->participants[$this->getUsrId()] = true;
						$this->readers[$this->getUsrId()]      = true;
					}
					break;

				default:
					$reader_roles[] = $role_id;
					if($rbacreview->isAssigned($this->getUsrId(), $role_id))
					{
						$this->participants[$this->getUsrId()] = true;
						$this->readers[$this->getUsrId()]      = true;
					}
					break;
			}
		}

		$this->numReaders = $rbacreview->getNumberOfAssignedUsers((array)$reader_roles);
	}

	/**
	 *
	 */
	protected function readParticipantStatus()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$query                     = 'SELECT * FROM rep_robj_xgdo_members WHERE obj_id = ' . $ilDB->quote($this->getObjId(), 'integer') . ' AND usr_id = ' . $ilDB->quote($this->getUsrId(), 'integer');
		$res                       = $ilDB->query($query);
		$this->participants_status = array();
		while($row = $ilDB->fetchAssoc($res))
		{
			$this->participants_status[$this->getUsrId()]['google_account'] = $row['google_account'];
		}
	}

	/**
	 * @param integer $a_usr_id
	 * @param string  $a_google_account
	 */
	public function updateGoogleAccount($a_google_account)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$this->participants_status[$this->getUsrId()]['google_account'] = $a_google_account;

		$query = "SELECT * FROM rep_robj_xgdo_members WHERE obj_id = " . $ilDB->quote($this->getObjId(), 'integer') . " AND usr_id = " . $ilDB->quote($this->getUsrId(), 'integer');
		$res   = $ilDB->query($query);
		if($ilDB->numRows($res))
		{
			$query = "
				UPDATE rep_robj_xgdo_members
				SET google_account = " . $ilDB->quote($a_google_account, 'text') . "
				WHERE obj_id = " . $ilDB->quote($this->getObjId(), 'integer') . "
				AND usr_id = " . $ilDB->quote($this->getUsrId(), 'integer');
		}
		else
		{
			$query = "
				INSERT INTO rep_robj_xgdo_members
				(google_account, obj_id, usr_id) " .
				"VALUES ( " .
				$ilDB->quote($a_google_account, 'text') . ", " .
				$ilDB->quote($this->getObjId(), 'integer') . ", " .
				$ilDB->quote($this->getUsrId(), 'integer') . "
				)
			";
		}
		$ilDB->manipulate($query);
	}

	/**
	 * @param int $type
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	public function add($type)
	{
		/**
		 * @var $rbacadmin  ilRbacAdmin
		 * @var $rbacreview ilRbacReview
		 */
		global $rbacadmin, $rbacreview;

		switch($type)
		{
			case self::GDOC_WRITER:
				$this->participants[$this->getUsrId()] = true;
				$this->writers[$this->getUsrId()]      = true;
				break;

			case self::GDOC_READER:
				$this->participants[$this->getUsrId()] = true;
				$this->readers[$this->getUsrId()]      = true;
				break;

			default:
				throw new InvalidArgumentException("Invalid role type {$type} given");
				break;
		}

		if(!($already_assigned = $rbacreview->isAssigned($this->getUsrId(), $this->role_data[$type])))
		{
			$rbacadmin->assignUser($this->role_data[$type], $this->getUsrId());
		}

		$this->addDesktopItem();

		return !$already_assigned;
	}

	/**
	 *
	 */
	public function delete()
	{
		/**
		 * @var $rbacadmin  ilRbacAdmin
		 * @var $ilDB       ilDB
		 */
		global $rbacadmin, $ilDB;

		$this->dropDesktopItem();

		foreach($this->roles as $role_id)
		{
			$rbacadmin->deassignUser($role_id, $this->getUsrId());
		}

		$query = "DELETE FROM rep_robj_xgdo_members WHERE usr_id = " . $ilDB->quote($this->getUsrId(), 'integer') . " AND obj_id = " . $ilDB->quote($this->getObjId(), 'integer');
		$ilDB->manipulate($query);

		$this->readParticipant();
		$this->readParticipantStatus();
	}

	/**
	 * @return bool
	 */
	public function addDesktopItem()
	{
		if(!ilObjUser::_isDesktopItem($this->getUsrId(), $this->getRefId(), $this->getType()))
		{
			ilObjUser::_addDesktopItem($this->getUsrId(), $this->getRefId(), $this->getType());
			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function dropDesktopItem()
	{
		if(ilObjUser::_isDesktopItem($this->getUsrId(), $this->getRefId(), $this->getType()))
		{
			ilObjUser::_dropDesktopItem($this->getUsrId(), $this->getRefId(), $this->getType());
			return true;
		}

		return false;
	}

	/**
	 * @return int
	 */
	public function getObjId()
	{
		return $this->obj_id;
	}

	/**
	 * @return int
	 */
	public function getRefId()
	{
		return $this->ref_id;
	}

	/**
	 * @return string
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @return int
	 */
	public function getUsrId()
	{
		return $this->usr_id;
	}

	/**
	 * @return array
	 */
	public function isReader()
	{
		return (bool)$this->readers[$this->getUsrId()];
	}

	/**
	 * @return bool
	 */
	public function isWriter()
	{
		return (bool)$this->writers[$this->getUsrId()];
	}

	/**
	 * @return bool
	 */
	public function isParticipant()
	{
		return (bool)$this->participants[$this->getUsrId()];
	}

	/**
	 * @return int
	 */
	public function getNumberOfReaders()
	{
		return $this->numReaders;
	}

	/**
	 * @return string|null
	 */
	public function getGoogleAccount()
	{
		return $this->participants_status[$this->getUsrId()]['google_account'];
	}

	/**
	 * @return bool
	 */
	public function hasGoogleAccount()
	{
		return isset($this->participants_status[$this->getUsrId()]['google_account']) && strlen($this->participants_status[$this->getUsrId()]['google_account']);
	}

	/**
	 * @return bool
	 */
	public function isAssigned()
	{
		return (bool)$this->participants[$this->getUsrId()];
	}
}
