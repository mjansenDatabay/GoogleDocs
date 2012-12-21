<?php

/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Docs
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * Zend_Gdata_Query
 */
require_once('Zend/Gdata/Query.php');

/**
 * Assists in constructing queries for Google Document List document ACL entries
 *
 * @link http://code.google.com/apis/documents/docs/2.0/developers_guide_protocol.html#AccessControlLists
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Docs
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Gdata_Docs_AclQuery extends Zend_Gdata_Query
{

    /**
     * The base URL for retrieving a document ACL list
     * @var string
     */
    const DOCUMENTS_LIST_ACL_FEED_URI = 'https://docs.google.com/feeds/acl/private/full';

    /**
     * The identifier for the user ID token.
     * @var string
     */
    const BATCH_USER_IDENTIFIER = '/user%3A';

    /**
     * The document type for accessing the ACL.
     * @var string
     */
    protected $_documentType = 'document';

    /**
     * The identifier for the document.
     */
    protected $_documentId;

    /**
     * The user identifier needed for ACL modification.
     * @var string
     */
    protected $_userId;

    /**
     * Whether to process the ACL queries in batch mode.
     * @var boolean
     */
    protected $_batch;

    /**
     * Sets the document type for accessing the ACL.
     * This value is normally provided if a URL is specified in the constructor.
     * @param string $docType
     * @return Zend_Gdata_Docs_AclQuery Provides a fluent interface.
     */
    public function setDocumentType($docType)
    {
        $this->_documentType = $docType;
        return $this;
    }

    /**
     * Gets the document type of the ACL that is being queried.
     * @return string
     */
    public function getDocumentType()
    {
        return $this->_documentType;
    }

    /**
     * Sets the document ID of the document ACL being queried.
     * @param string $value
     * @return Zend_Gdata_Docs_Query Provides a fluent interface
     */
    public function setDocumentId($docId)
    {
        $this->_documentId = $docId;
        return $this;
    }

    /**
     * Gets the document ID of the document ACL being queried.
     * @return string
     */
    public function getDocumentId()
    {
        return $this->_documentId;
    }

    /**
     * Sets the identifier for the user for creating the query URL.
     * This method checks for the existence of the '@' symbol and, if present,
     * runs the value through urlencode().
     * @param string $userId The identifier of the user, including domain name.
     * If the parameter is empty, it acts as though it is clearing the user ID.
     * If the internal storage of the URL already contains the GET parameters
     * for the user ID, it is removed if this parameter is empty.
     * @return Zend_Gdata_Docs_AclQuery Provides a fluent interface
     */
    public function setUserId($userId)
    {
        if (strpos($userId, '@')) {
            $this->_userId = urlencode($userId);
        } else {
            $this->_userId = $userId;
        }

        return $this;
    }

    /**
     * Gets the identifier of the user.
     * @return string
     */
    public function getUserId()
    {
        return $this->_userId;
    }

    /**
     * Sets whether the query should be processed in batch mode.
     * @param boolean $value A true/false value stating whether to access
     * the batch API
     * @return Zend_Gdata_Docs_AclQuery Provides a fluent interface
     */
    public function setBatch($value)
    {
        $this->_batch = $value;
        return $this;
    }

    /**
     * Gets the batch mode boolean value.
     * @return boolean
     */
    public function getBatch()
    {
        return $this->_batch;
    }

    /**
     * Gets the full query URL for this query.
     *
     * @return string url
     */
    public function getQueryUrl()
    {
        if ($this->_url) {
            $suffix = '';
            if ($this->_batch) {
                $suffix = '/batch';
            } else {    //user ID should only be set if batch mode is false
                if ($this->_userId) {
                    $suffix = self::BATCH_USER_IDENTIFIER . $this->_userId;
                }
            }
            return $this->_url . $suffix;
        }

        $uri = self::DOCUMENTS_LIST_ACL_FEED_URI;
        $uri .= '/' . $this->_documentType;
        if ($this->_documentId) {
            $uri .= '%3A' . $this->_documentId;
        } else {
            require_once('Zend/Gdata/App/InvalidArgumentException.php');
            throw new Zend_Gdata_App_InvalidArgumentException('No document identifier specified.');
        }
        if ($this->_batch) {
            $uri .= '/batch';
        } else {    //user ID should only be set if batch mode is false
            if ($this->_userId) {
                $uri .= self::BATCH_USER_IDENTIFIER . $this->_userId;
            }
        }
        $uri .= $this->getQueryString();
        return $uri;
    }

}
