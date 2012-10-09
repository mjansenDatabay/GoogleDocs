<?php

class ilGoogleDocsAPI
{
	/**
	 * @var $instance 
	 */
	private static $instance;

	private $login = null;
	private $password = null;
	
	private $tbl_settings = 'rep_robj_xgdo_settings';

	/**
	 * @var $client object Zend_Gdata_ClientLogin
	 */
	public $client = null;
	/**
	 * @var docs object Zend_Gdata_Docs
	 */
	public $docs = null;


	/**
	 * @param $login string
	 */
	public function setLogin($login)
	{
		$this->login = $login;
	}

	public function getLogin()
	{
		return $this->login;
	}

	/**
	 * @param $password string
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}

	public function getPassword()
	{
		return $this->password;
	}
	

	private function __construct()
	{
	}
	
	public static function getInstance()
	{
		if(!self::$instance instanceof self)
		{
			if(self::getSetting('login') == NULL && self::getSetting('password') == NULL)
			{
				global $lng;
				return ilUtil::sendFailure($lng->txt('err_check_input'));
			}
			else
			{
				self::$instance = new self;
				self::$instance->connect();
			}
		}
		return self::$instance;
	}
	
	private function connect()
	{ 
		if(!$this->readSettings())
		{
			return false;
		}
		
		try {
			$client = Zend_Gdata_ClientLogin::getHttpClient($this->getLogin(), $this->getPassword(), Zend_Gdata_Docs::AUTH_SERVICE_NAME);
			$docs = new Zend_Gdata_Docs($client);
			$feed = $docs->getDocumentListFeed();
			$this->client = $client;
			$this->docs   = $docs;

		} catch (Zend_Gdata_App_AuthException $ae)
		{
			global $lng;
			return ilUtil::sendFailure($lng->txt('err_wrong_login'));
		}
		
		return true;
	}

	/**
	 * @static
	 * @param $pluginObj object GoogleDocsPlugin-Object
	 * @param $a_login	 string  Google Login	
	 * @param $a_password string Google Password
	 */
	public static function checkConnection($pluginObj, $a_login, $a_password)
	{
		try {
			$client = Zend_Gdata_ClientLogin::getHttpClient($a_login, $a_password, Zend_Gdata_Docs::AUTH_SERVICE_NAME);
			
			$docs = new Zend_Gdata_Docs($client);
			$feed = $docs->getDocumentListFeed();
			
		} catch (Zend_Gdata_App_AuthException $ae)
		{
			global $lng;	
			return ilUtil::sendFailure($lng->txt('err_wrong_login'));
		}
		return true;
	}

	public function readSettings()
	{
		global $ilDB;

		$res = $ilDB->queryF('
			SELECT * FROM '.$this->tbl_settings .' 
			WHERE keyword = %s
			OR keyword = %s',
			array('text', 'text'),
			array('login', 'password'));

		if($ilDB->numRows($res) < 2)
		{ 
			return 0;
		}
		else
		{
			while($row = $ilDB->fetchAssoc($res))
			{
				$settings[$row['keyword']] = $row['value'];
				$this->login = $settings['login'];
				$this->password = $settings['password'];
			}
			return true;
		}
	}

	/**
	 * @static
	 * @param $a_keyword string 
	 * @param $a_value string 
	 */
	public static function setSetting($a_keyword, $a_value)
	{
		global $ilDB;
		
		$ilDB->manipulateF('
			DELETE FROM rep_robj_xgdo_settings WHERE keyword = %s',
			array('text'), array($a_keyword));
		
		$ilDB->insert('rep_robj_xgdo_settings', 
			array( 
			'keyword' => array('text', $a_keyword),
			'value' => array('text', $a_value)));
	}

	/**
	 * @static
	 * @param $a_keyword
	 * @return mixed
	 */
	public static function getSetting($a_keyword)
	{
		global $ilDB;
		
		$res = $ilDB->queryF('SELECT value FROM rep_robj_xgdo_settings WHERE keyword = %s',
		array('text'), array($a_keyword));
		
		$row = $ilDB->fetchAssoc($res);
		
		return $row['value'];
	}


	/**
	 * @param $a_title  string Name of the new file
	 * @param $a_type	integer Type of the file
	 * @return mixed
	 */
	public function createDocumentByType($a_title, $a_type)
	{
// Create new document
		$data = new Zend_Gdata_Docs_DocumentListEntry();

		if($a_type == ilGoogleDocsConstants::GOOGLE_DOC)
		{
			$doctype = 'document';
		}
		else if($a_type == ilGoogleDocsConstants::GOOGLE_XLS)
		{
			$doctype = 'spreadsheet';
		}
		else if($a_type == ilGoogleDocsConstants::GOOGLE_PPT)
		{
			$doctype = 'presentation';
		}

		$data->setCategory(
			array(new Zend_Gdata_App_Extension_Category(
				"http://schemas.google.com/docs/2007#".$doctype,
				"http://schemas.google.com/g/2005#kind"
			)));

		$data->setTitle(new Zend_Gdata_App_Extension_Title($a_title, null));

// Add document to your list
		$doc = $this->docs->insertDocument($data, Zend_Gdata_Docs::DOCUMENTS_LIST_FEED_URI);

// Return document ID
		return ($doc->getId());
	}
	
	public function copyDocument($a_doc_url, $a_type, $a_title)
	{

// Create new document
		$data = new Zend_Gdata_Docs_DocumentListEntry();

		if($a_type == ilGoogleDocsConstants::GOOGLE_DOC)
		{
			$doctype = 'document';
		}
		else if($a_type == ilGoogleDocsConstants::GOOGLE_XLS)
		{
			$doctype = 'spreadsheet';
		}
		else if($a_type == ilGoogleDocsConstants::GOOGLE_PPT)
		{
			$doctype = 'presentation';
		}

		$data->setCategory(
			array(new Zend_Gdata_App_Extension_Category(
				"http://schemas.google.com/docs/2007#".$doctype,
				"http://schemas.google.com/g/2005#kind"
			)));

		$data->setTitle(new Zend_Gdata_App_Extension_Title($a_title, null));
// Use this doc_id as copy source	
		$data->setId(new Zend_Gdata_App_Extension_Id($a_doc_url));

// Add document to your list
		$doc = $this->docs->insertDocument($data, Zend_Gdata_Docs::DOCUMENTS_LIST_FEED_URI);
//
// Return document ID
		return ($doc->getId());		
		
	}

	/**
	 * @param $a_doc_url string i.e. "https://docs.google.com/feeds/documents/private/full/document%3A1YrgFUy....."
	 */
	public function deleteDocumentByUrl($a_doc_url)
	{
		$file = $this->docs->getDocumentListEntry($a_doc_url);
		$file->delete();
	}
}