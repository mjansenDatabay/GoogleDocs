<?php
/* Copyright (c) 1998-2012 ILIAS open source, Extended GPL, see docs/LICENSE */

require_once dirname(__FILE__) . '/../interfaces/interface.ilGoogleDocsConstants.php';

/**
 *
 */
class ilGoogleDocsAPI implements ilGoogleDocsConstants
{
	/**
	 * @var ilGoogleDocsAPI[]
	 */
	private static $instances = array();

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
	 * @var Zend_Gdata_Docs|Zend_Gdata_Spreadsheets
	 */
	public $document_service = null;

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
	 * @param Zend_Gdata_Docs $docs|Zend_Gdata_Spreadsheets
	 */
	public function setDocumentService(Zend_Gdata $document_service)
	{
		$this->document_service = $document_service;
	}

	/**
	 * @return Zend_Gdata_Docs|Zend_Gdata_Spreadsheets
	 */
	public function getDocumentService()
	{
		return $this->document_service;
	}

	/**
	 * @param $status null|void
	 * @return void|boolean
	 */
	public function isProxyEnabled($status = null)
	{
		if(null === $status)
		{
			return $this->is_proxy_enabled;
		}

		$this->is_proxy_enabled = $status; 
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
	 * @param string $login
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

	/**
	 *
	 */
	protected function __construct()
	{
		require_once 'Services/Http/classes/class.ilProxySettings.php';

		if(ilProxySettings::_getInstance()->isActive())
		{
			$this->isProxyEnabled(true);
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
		return self::getInstanceByType(self::DOC_TYPE_DOCUMENT);
	}

	/**
	 * @param string $type
	 * @return ilGoogleDocsAPI
	 * @throws InvalidArgumentException
	 */
	public static function getInstanceByType($type)
	{
		if(!in_array($type, array(
			self::DOC_TYPE_DOCUMENT,
			self::DOC_TYPE_SPREADSHEET,
			self::DOC_TYPE_PRESENTATION
		))
		)
		{
			throw new InvalidArgumentException("Document type {$type} currently not supported");
		}

		if(!self::$instances[$type] instanceof self)
		{
			self::$instances[$type] = new self($type);
			self::$instances[$type]->connect($type);
		}

		return self::$instances[$type];
	}

	/**
	 * @param int $type
	 * @throws ilException
	 */
	protected function connect($type = self::DOC_TYPE_DOCUMENT)
	{
		if(!$this->readSettings())
		{
			throw new ilException(self::ERROR_ACCOUNT_DATA);
		}

		$proxiedHttpClient = null;
		if($this->isProxyEnabled())
		{
			$config = array(
				'adapter'    => 'Zend_Http_Client_Adapter_Proxy',
				'proxy_host' => $this->getProxyHost(),
				'proxy_port' => $this->getProxyPort()
			);
			$proxiedHttpClient = new Zend_Gdata_HttpClient('http://www.google.com:443', $config);
		}

		$client = Zend_Gdata_ClientLogin::getHttpClient($this->getLogin(), $this->getPassword(), self::getGoogleAuthServiceNameByIliasType($type), $proxiedHttpClient);
		$this->setClient($client);
		$this->setDocumentService(self::createGoogleDocumentServiceByIliasType($client, $type));
	}

	/**
	 * @param Zend_Gdata_HttpClient $client
	 * @param int                   $type
	 * @return Zend_Gdata_Docs|Zend_Gdata_Spreadsheets
	 * @throws InvalidArgumentException
	 */
	protected static function createGoogleDocumentServiceByIliasType(Zend_Gdata_HttpClient $client, $type)
	{
		if(self::DOC_TYPE_DOCUMENT == $type)
		{
			return new Zend_Gdata_Docs($client);
		}
		else if(self::DOC_TYPE_SPREADSHEET == $type)
		{
			return new Zend_Gdata_Spreadsheets($client);
		}
		else if(self::DOC_TYPE_PRESENTATION == $type)
		{
			return new Zend_Gdata_Docs($client);
		}
		else
		{
			throw new InvalidArgumentException("Document type {$type} is currently not supported");
		}
	}

	/**
	 * @param int $type
	 * @return string
	 * @throws InvalidArgumentException
	 */
	protected static function getGoogleAuthServiceNameByIliasType($type)
	{
		if(self::DOC_TYPE_DOCUMENT == $type)
		{
			return Zend_Gdata_Docs::AUTH_SERVICE_NAME;
		}
		else if(self::DOC_TYPE_SPREADSHEET == $type)
		{
			return Zend_Gdata_Spreadsheets::AUTH_SERVICE_NAME;
		}
		else if(self::DOC_TYPE_PRESENTATION == $type)
		{
			return Zend_Gdata_Docs::AUTH_SERVICE_NAME;
		}
		else
		{
			throw new InvalidArgumentException("Document type {$type} is currently not supported");
		}
	}

	/**
	 * @param int $type
	 * @return string
	 * @throws InvalidArgumentException
	 */
	protected static function getGoogleDocsTypeByIliasType($type)
	{
		if(self::DOC_TYPE_DOCUMENT == $type)
		{
			$document_type = 'document';
		}
		else if(self::DOC_TYPE_SPREADSHEET == $type)
		{
			$document_type = 'spreadsheet';
		}
		else if(self::DOC_TYPE_PRESENTATION == $type)
		{
			$document_type = 'presentation';
		}
		else
		{
			throw new InvalidArgumentException("Document type {$type} is currently not supported");
		}

		return $document_type;
	}

	/**
	 * @param string $edit_doc_url
	 * @return int
	 */
	public static function getIliasTypeByGoogleEditUrl($edit_doc_url)
	{
		if(preg_match('@/presentation/@', $edit_doc_url))
		{
			return self::DOC_TYPE_PRESENTATION;
		}
		else if(preg_match('@/spreadsheet/@', $edit_doc_url))
		{
			return self::DOC_TYPE_SPREADSHEET;
		}
		else
		{
			return self::DOC_TYPE_DOCUMENT;
		}
	}

	/**
	 * @param string   $login
	 * @param string   $password
	 * @return bool
	 * @throws Exception
	 */
	public static function checkConnection($login, $password)
	{
		require_once 'Services/Http/classes/class.ilProxySettings.php';

		$proxiedHttpClient = null;
		if(ilProxySettings::_getInstance()->isActive())
		{
			$config = array(
				'adapter'    => 'Zend_Http_Client_Adapter_Proxy',
				'proxy_host' => ilProxySettings::_getInstance()->getHost(),
				'proxy_port' => ilProxySettings::_getInstance()->getPort()
			);
			$proxiedHttpClient = new Zend_Gdata_HttpClient('http://www.google.com:443', $config);
		}

		$client = Zend_Gdata_ClientLogin::getHttpClient($login, $password, self::getGoogleAuthServiceNameByIliasType(self::DOC_TYPE_DOCUMENT), $proxiedHttpClient);
		$docs   = self::createGoogleDocumentServiceByIliasType($client, self::DOC_TYPE_DOCUMENT);
		$docs->getDocumentListFeed();
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
			SELECT * FROM ' . $this->tbl_settings . ' WHERE keyword = %s OR keyword = %s',
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
	 * @param string $keyword
	 * @param string $value
	 */
	public static function setSetting($keyword, $value)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$ilDB->manipulateF('
			DELETE FROM rep_robj_xgdo_settings WHERE keyword = %s',
			array('text'), array($keyword));

		$ilDB->insert(
			'rep_robj_xgdo_settings',
			array(
				'keyword' => array('text', $keyword),
				'value'   => array('text', $value)
			)
		);
	}

	/**
	 * @param string $keyword
	 * @return string|null
	 */
	public static function getSetting($keyword)
	{
		/**
		 * @var $ilDB ilDB
		 */
		global $ilDB;

		$res = $ilDB->queryF(
			'SELECT value FROM rep_robj_xgdo_settings WHERE keyword = %s',
			array('text'),
			array($keyword)
		);
		$row = $ilDB->fetchAssoc($res);

		return $row['value'];
	}


	/**
	 * @param string $title   string Name of the new file
	 * @param string $type    integer Type of the file
	 * @return Zend_Gdata_Docs_DocumentListEntry
	 */
	public function createDocumentByType($title, $type)
	{
		$entry = new Zend_Gdata_Docs_DocumentListEntry();

		$entry->setCategory(
			array(
				new Zend_Gdata_App_Extension_Category(
					"http://schemas.google.com/docs/2007#" . self::getGoogleDocsTypeByIliasType($type),
					"http://schemas.google.com/g/2005#kind"
				)
			)
		);
		$entry->setTitle(new Zend_Gdata_App_Extension_Title($title, null));
		return $this->getDocumentService()->insertDocument($entry, Zend_Gdata_Docs::DOCUMENTS_LIST_FEED_URI);
	}

	/**
	 * @param string $title
	 * @param string $path
	 * @param string $mime_type
	 * @return Zend_Gdata_Docs_DocumentListEntry
	 */
	public function createDocumentByFile($title, $path, $mime_type)
	{
		return $this->getDocumentService()->uploadFile($path, $title, $mime_type, Zend_Gdata_Docs::DOCUMENTS_LIST_FEED_URI);
	}

	/**
	 * @param string $url
	 * @param int    $type
	 * @param string $title
	 * @return mixed
	 */
	public function copyDocument($url, $type, $title)
	{
		$entry = new Zend_Gdata_Docs_DocumentListEntry();

		$entry->setCategory(
			array(
				new Zend_Gdata_App_Extension_Category(
					"http://schemas.google.com/docs/2007#" . self::getGoogleDocsTypeByIliasType($type),
					"http://schemas.google.com/g/2005#kind"
				)
			)
		);
		$entry->setTitle(new Zend_Gdata_App_Extension_Title($title, null));
		$entry->setId(new Zend_Gdata_App_Extension_Id($url));
		$doc = $this->getDocumentService()->insertDocument($entry, Zend_Gdata_Docs::DOCUMENTS_LIST_FEED_URI);

		return $doc->getId();

	}

	/**
	 * @param string $url i.e. "https://docs.google.com/feeds/documents/private/full/document%3A1YrgFUy....."
	 */
	public function deleteDocumentByUrl($url)
	{
		$file = $this->getDocumentService()->getDocumentListEntry($url);
		$file->delete();
	}
}
