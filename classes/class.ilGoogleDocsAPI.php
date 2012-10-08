<?php

class ilGoogleDocsAPI
{
	/** @var 
	  */
	private static $instance;

	private $login = null;
	private $password = null;
	
	private $tbl_settings = 'rep_robj_xgdo_settings';
	
	public $client = null;
	public $docs = null;

	
	public function setLogin($login)
	{
		$this->login = $login;
	}

	public function getLogin()
	{
		return $this->login;
	}

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
			self::$instance = new self;
			self::$instance->connect();
		}
		return self::$instance;
	}
	
	private function connect()
	{ 
		if(!$this->readSettings())
		{
			global $lng;
			return ilUtil::sendFailure($lng->txt('settings_incomplete'));	
		}

		try {
			$client = Zend_Gdata_ClientLogin::getHttpClient($this->getLogin(), $this->getPassword(), Zend_Gdata_Docs::AUTH_SERVICE_NAME);
			$docs = new Zend_Gdata_Docs($client);
			$feed = $docs->getDocumentListFeed();
		} catch (Zend_Gdata_App_AuthException $ae)
		{
			exit("Error: ". $ae->getMessage() ."\nCredentials provided were email: [$this->getLogin()] and password [$this->getPassword()].\n");
		}
/*
		if(!isset($_SESSION['token']))
		{
			if(isset($_GET['token']))
			{
				$session_token         = Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token']);
				$_SESSION['token'] = $session_token;
			}
			else
			{
				$googleUri = Zend_Gdata_AuthSub::getAuthSubTokenUri(
					'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
					'https://docs.google.com/feeds/', 0, 1
				);
				header("Location: " . $googleUri);
				exit();
			}
		}
*/
		$this->client = $client;
		$this->docs   = new Zend_Gdata_Docs($client);
	
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
			exit("Error: ". $ae->getMessage() ."\nCredentials provided were email: [$a_login] and password [******].\n");
		}

/*		if(!isset($_SESSION['token']))
		{
			if(isset($_GET['token']))
			{
				$session_token         = Zend_Gdata_AuthSub::getAuthSubSessionToken($_GET['token'], $client);

				$_SESSION['token'] = $session_token;
			}
			else
			{
				$googleUri = Zend_Gdata_AuthSub::getAuthSubTokenUri(
					'https://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'],
					'https://docs.google.com/feeds/', 0, 1
				);
				header("Location: " . $googleUri);
				exit();
			}
		}
	
		$client = Zend_Gdata_AuthSub::getHttpClient($_SESSION['token']);
		$docs   = new Zend_Gdata_Docs($client);
		$feed = $docs->getDocumentListFeed();
*/		
		foreach($feed->entries as $document)
		{
			echo "<hr />";
			echo '<a href="'.$document->getContent()->getSrc().'">'.$document->getTitle()->getText()."</a><br />";
			foreach($document->getLink() as $link)
			{
				if($link->getRel() == 'alternate')
				{
					echo '<a href="'.$link->getHref().'">Am Dokument arbeiten</a><br />';
				}
			}
		}
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
			return false;
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


	public function setSetting($a_keyword, $a_value)
	{
		global $ilDB;
		
		$ilDB->manipulateF('
			DELETE FROM '. $this->tbl_settings.' WHERE keyword = %s',
			array('text'), array($a_keyword));
		
		$ilDB->insert($this->tbl_settings, 
			array( 
			'keyword' => array('text', $a_keyword),
			'value' => array('text', $a_value)));
	}
	
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

// Display document ID
		return ($doc->getId());
	}
	
	public function deleteDocumentByUrl($a_doc_url)
	{
		//"https://docs.google.com/feeds/documents/private/full/document%3A1YrgFUyyDdCIYJwD2iVs-MWzHUHmMAHMzsLMpn2ivrXU";
		$file = $this->docs->getDocumentListEntry($a_doc_url);
		$file->delete();


	}


}