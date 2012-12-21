<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';

/**
 *
 */
class ilGoogleDocsAPI implements ilGoogleDocsConstants
{
	/**
	 * @var ilGoogleDocsAPI
	 */
	private static $instance;

	/**
	 * @var string Google-Login
	 */
	private $login = null;

	/**
	 * @var string Google-Password
	 */
	private $password = null;

	/**
	 * @var string
	 */
	private $tbl_settings = 'rep_robj_xgdo_settings';

	/**
	 * @var Zend_Gdata_ClientLogin
	 */
	public $client = null;

	/**
	 * @var Zend_Gdata_Docs
	 */
	public $docs = null;

	/**
	 * @var bool
	 */
	public $is_proxy_enabled = false;

	/**
	 * @var string
	 */
	private $proxy_host = '';

	/**
	 * @var  string
	 */
	private $proxy_port = '';

	/**
	 * @param Zend_Gdata_ClientLogin $client
	 */
	public function setClient($client)
	{
		$this->client = $client;
	}

	/**
	 * @return Zend_Gdata_ClientLogin
	 */
	public function getClient()
	{
		return $this->client;
	}

	/**
	 * @param Zend_Gdata_Docs $docs
	 */
	public function setDocs(Zend_Gdata_Docs $docs)
	{
		$this->docs = $docs;
	}

	/**
	 * @return Zend_Gdata_Docs
	 */
	public function getDocs()
	{
		return $this->docs;
	}

	/**
	 * @param boolean $is_proxy_enabled
	 */
	public function setIsProxyEnabled($is_proxy_enabled)
	{
		$this->is_proxy_enabled = $is_proxy_enabled;
	}

	/**
	 * @return boolean
	 */
	public function getIsProxyEnabled()
	{
		return $this->is_proxy_enabled;
	}

	/**
	 * @param string $proxy_host
	 */
	public function setProxyHost($proxy_host)
	{
		$this->proxy_host = $proxy_host;
	}

	/**
	 * @return string
	 */
	public function getProxyHost()
	{
		return $this->proxy_host;
	}

	/**
	 * @param string $proxy_port
	 */
	public function setProxyPort($proxy_port)
	{
		$this->proxy_port = $proxy_port;
	}

	/**
	 * @return string
	 */
	public function getProxyPort()
	{
		return $this->proxy_port;
	}

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

		if(version_compare(ILIAS_VERSION_NUMERIC, '4.3.0', '>='))
		{
			require_once 'Services/Http/classes/class.ilProxySettings.php';
		}
		else
		{
			require_once 'classes/class.ilProxySettings.php';
		}

		if(ilProxySettings::_getInstance()->isActive())
		{
			$this->setIsProxyEnabled(true);
			$this->setProxyHost(ilProxySettings::_getInstance()->getHost());
			$this->setProxyPort(ilProxySettings::_getInstance()->getPort());
		}
	}

	/**
	 * @return ilGoogleDocsAPI
	 * @throws Exception
	 */
	public static function getInstance()
	{
		if(!self::$instance instanceof self)
		{
			self::$instance = new self;
			self::$instance->connect();
		}

		return self::$instance;
	}

	/**
	 * @throws Exception
	 */
	private function connect()
	{
		if(!$this->readSettings())
		{
			throw new ilException(self::ERROR_ACCOUNT_DATA);
		}

		if($this->is_proxy_enabled == true)
		{
			$config = array(
				'adapter'    => 'Zend_Http_Client_Adapter_Proxy',
				'proxy_host' => $this->getProxyHost(),
				'proxy_port' => $this->getProxyPort()
			);

			$proxiedHttpClient = new Zend_Gdata_HttpClient('http://www.google.com:443', $config);
			$client            = Zend_Gdata_ClientLogin::getHttpClient($this->getLogin(), $this->getPassword(), Zend_Gdata_Docs::AUTH_SERVICE_NAME, $proxiedHttpClient);
			$docs              = new Zend_Gdata_Docs($client);
			$this->setClient($client);
			$this->setDocs($docs);
		}
		else
		{
			$client = Zend_Gdata_ClientLogin::getHttpClient($this->getLogin(), $this->getPassword(), Zend_Gdata_Docs::AUTH_SERVICE_NAME);
			$docs   = new Zend_Gdata_Docs($client);
			$this->setClient($client);
			$this->setDocs($docs);
		}
	}

	/**
	 * @param ilPlugin $pluginObj
	 * @param string   $a_login
	 * @param string   $a_password
	 * @return bool
	 * @throws Exception
	 */
	public static function checkConnection(ilPlugin $pluginObj, $a_login, $a_password)
	{
		if(version_compare(ILIAS_VERSION_NUMERIC, '4.3.0', '>='))
		{
			require_once 'Services/Http/classes/class.ilProxySettings.php';
		}
		else
		{
			require_once 'classes/class.ilProxySettings.php';
		}

		if(ilProxySettings::_getInstance()->isActive())
		{
			$config = array(
				'adapter'    => 'Zend_Http_Client_Adapter_Proxy',
				'proxy_host' => ilProxySettings::_getInstance()->getHost(),
				'proxy_port' => ilProxySettings::_getInstance()->getPort()
			);

			$proxiedHttpClient = new Zend_Gdata_HttpClient('http://www.google.com:443', $config);
			$client            = Zend_Gdata_ClientLogin::getHttpClient($a_login, $a_password, Zend_Gdata_Docs::AUTH_SERVICE_NAME, $proxiedHttpClient);
			$docs              = new Zend_Gdata_Docs($client);
			$docs->getDocumentListFeed();
		}
		else
		{
			$client = Zend_Gdata_ClientLogin::getHttpClient($a_login, $a_password, Zend_Gdata_Docs::AUTH_SERVICE_NAME);
			$docs   = new Zend_Gdata_Docs($client);
			$docs->getDocumentListFeed();
		}

		return true;
	}

	/**
	 * @return bool|int
	 */
	public function readSettings()
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$res = $ilDB->queryF('
			SELECT * FROM ' . $this->tbl_settings . ' 
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
				$this->setLogin($settings['login']);
				$this->setPassword($settings['password']);
			}

			return true;
		}
	}

	/**
	 * @param $a_keyword string
	 * @param $a_value   string
	 */
	public static function setSetting($a_keyword, $a_value)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$ilDB->manipulateF('
			DELETE FROM rep_robj_xgdo_settings WHERE keyword = %s',
			array('text'), array($a_keyword));

		$ilDB->insert(
			'rep_robj_xgdo_settings',
			array(
				'keyword' => array('text', $a_keyword),
				'value'   => array('text', $a_value)
			)
		);
	}

	/**
	 * @param string $a_keyword
	 * @return string|null
	 */
	public static function getSetting($a_keyword)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$res = $ilDB->queryF(
			'SELECT value FROM rep_robj_xgdo_settings WHERE keyword = %s',
			array('text'),
			array($a_keyword)
		);

		$row = $ilDB->fetchAssoc($res);

		return $row['value'];
	}


	/**
	 * @param string $a_title   string Name of the new file
	 * @param string $a_type    integer Type of the file
	 * @return mixed
	 */
	public function createDocumentByType($a_title, $a_type)
	{
		$data = new Zend_Gdata_Docs_DocumentListEntry();

		if($a_type == self::GOOGLE_DOC)
		{
			$doctype = 'document';
		}
		else if($a_type == self::GOOGLE_XLS)
		{
			$doctype = 'spreadsheet';
		}
		else if($a_type == self::GOOGLE_PPT)
		{
			$doctype = 'presentation';
		}

		$data->setCategory(
			array(
				new Zend_Gdata_App_Extension_Category(
					"http://schemas.google.com/docs/2007#" . $doctype,
					"http://schemas.google.com/g/2005#kind"
				)
			)
		);

		$data->setTitle(new Zend_Gdata_App_Extension_Title($a_title, null));

// Add document to your list
		$doc = $this->docs->insertDocument($data, Zend_Gdata_Docs::DOCUMENTS_LIST_FEED_URI);

// Return document ID
		return $doc->getId();
	}

	/**
	 * @param string $a_doc_url
	 * @param int $a_type
	 * @param string $a_title
	 * @return mixed
	 */
	public function copyDocument($a_doc_url, $a_type, $a_title)
	{
// Create new document
		$data = new Zend_Gdata_Docs_DocumentListEntry();

		if($a_type == self::GOOGLE_DOC)
		{
			$doctype = 'document';
		}
		else if($a_type == self::GOOGLE_XLS)
		{
			$doctype = 'spreadsheet';
		}
		else if($a_type == self::GOOGLE_PPT)
		{
			$doctype = 'presentation';
		}

		$data->setCategory(
			array(
				new Zend_Gdata_App_Extension_Category(
					"http://schemas.google.com/docs/2007#" . $doctype,
					"http://schemas.google.com/g/2005#kind"
				)
			));

		$data->setTitle(new Zend_Gdata_App_Extension_Title($a_title, null));
// Use this doc_id as copy source	
		$data->setId(new Zend_Gdata_App_Extension_Id($a_doc_url));

// Add document to your list
		$doc = $this->docs->insertDocument($data, Zend_Gdata_Docs::DOCUMENTS_LIST_FEED_URI);
//
// Return document ID
		return $doc->getId();

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
