<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';

/**
 *
 */
class ilGoogleDocsParticipants implements ilGoogleDocsConstants
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
	 */
	public function __construct($a_obj_id)
	{
		$this->obj_id = $a_obj_id;
		$this->type   = ilObject::_lookupType($a_obj_id);
		$ref_ids      = ilObject::_getAllReferences($this->obj_id);
		$this->ref_id = current($ref_ids);

		$this->readParticipants();
		$this->readParticipantsStatus();
	}

	/**
	 * @param int $a_obj_id
	 * @return ilGoogleDocsParticipants
	 */
	public static function getInstanceByObjId($a_obj_id)
	{
		if(isset(self::$instances[$a_obj_id]) && self::$instances[$a_obj_id] instanceof self)
		{
			return self::$instances[$a_obj_id];
		}

		return self::$instances[$a_obj_id] = new self($a_obj_id);
	}

	protected function readParticipants()
	{
		/**
		 * @var $rbacreview     ilRbacReview
		 * @var $ilObjDataCache ilObjectDataCache
		 */
		global $rbacreview, $ilObjDataCache;

		$rolf = $rbacreview->getRoleFolderOfObject($this->ref_id);

		if(!isset($rolf['ref_id']) or !$rolf['ref_id'])
		{
			return false;
		}

		$this->roles     = $rbacreview->getRolesOfRoleFolder($rolf['ref_id'], false);
		$this->role_data = $this->participants = $this->writers = $this->readers = array();

		foreach($this->roles as $role_id)
		{
			$title = $ilObjDataCache->lookupTitle($role_id);
			switch(substr($title, 0, 14))
			{
				case 'il_xgdo_writer':
					$this->role_data[self::GDOC_WRITER] = $role_id;
					$this->participants                 = array_unique(array_merge($this->participants));
					$this->writers                      = $rbacreview->assignedUsers($role_id);
					break;

				case 'il_xgdo_reader':
					$this->role_data[self::GDOC_READER] = $role_id;
					$this->participants                 = array_unique(array_merge($assigned = $rbacreview->assignedUsers($role_id), $this->participants));
					$this->readers                      = array_unique(array_merge($assigned, $this->readers));
					break;

				default:
					$this->participants = array_unique(array_merge($assigned = $rbacreview->assignedUsers($role_id), $this->participants));
					$this->readers      = array_unique(array_merge($assigned, $this->readers));
					break;
			}
		}
	}

	protected function readParticipantsStatus()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$query                     = 'SELECT * FROM rep_robj_xgdo_members WHERE obj_id = ' . $ilDB->quote($this->getObjId(), 'integer');
		$res                       = $ilDB->query($query);
		$this->participants_status = array();
		while($row = $ilDB->fetchAssoc($res))
		{
			$this->participants_status[$row['usr_id']]['google_account'] = $row['google_account'];
		}
	}

	/**
	 * @param int $usr_id
	 * @param int $type
	 * @return bool
	 */
	public function add($usr_id, $type)
	{
		/**
		 * @var $rbacadmin  ilRbacAdmin
		 * @var $rbacreview ilRbacReview
		 */
		global $rbacadmin, $rbacreview;

		switch($type)
		{
			case self::GDOC_WRITER:
				if(!in_array($usr_id, $this->participants))
				{
					$this->participants[] = $usr_id;
				}
				if(!in_array($usr_id, $this->writers))
				{
					$this->writers[] = $usr_id;
				}
				break;

			case self::GDOC_READER:
				if(!in_array($usr_id, $this->participants))
				{
					$this->participants[] = $usr_id;
				}
				if(!in_array($usr_id, $this->readers))
				{
					$this->readers[] = $usr_id;
				}
				break;

			default:
				throw new InvalidArgumentException("Invalid role type {$type} given");
				break;
		}

		if(!($already_assigned = $rbacreview->isAssigned($usr_id, $this->role_data[$type])))
		{
			$rbacadmin->assignUser($this->role_data[$type], $usr_id);
		}

		$this->addDesktopItem($usr_id);

		return !$already_assigned;
	}


	public function delete($usr_id)
	{
		/**
		 * @var $rbacadmin  ilRbacAdmin
		 * @var $ilDB       ilDB
		 */
		global $rbacadmin, $ilDB;

		$this->dropDesktopItem($usr_id);

		foreach($this->roles as $role_id)
		{
			$rbacadmin->deassignUser($role_id, $usr_id);
		}

		$query = "DELETE FROM rep_robj_xgdo_members WHERE usr_id = " . $ilDB->quote($usr_id, 'integer') . " AND obj_id = " . $ilDB->quote($this->getObjId(), 'integer');
		$ilDB->manipulate($query);

		$this->readParticipants();
		$this->readParticipantsStatus();
	}

	/**
	 * @param int $usr_id
	 * @return bool
	 */
	public function addDesktopItem($usr_id)
	{
		if(!ilObjUser::_isDesktopItem($usr_id, $this->getRefId(), $this->getType()))
		{
			ilObjUser::_addDesktopItem($usr_id, $this->getRefId(), $this->getType());
			return true;
		}

		return false;
	}

	/**
	 * @param int $usr_id
	 * @return bool
	 */
	public function dropDesktopItem($usr_id)
	{
		if(ilObjUser::_isDesktopItem($usr_id, $this->getRefId(), $this->getType()))
		{
			ilObjUser::_dropDesktopItem($usr_id, $this->getRefId(), $this->getType());
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
}
