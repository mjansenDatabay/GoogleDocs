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
 * @subpackage Batch
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see Zend_Gdata_Batch
 */
require_once 'Zend/Gdata/Batch.php';

/**
 * Represents the batch:status element
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Batch
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Gdata_Batch_Status extends Zend_Gdata_Batch
{
    /**
     * This element is represented as <status> so need to override <entry>.
     * @var string
     */
    protected $_rootElement = 'status';
    /**
     * The status code returned from the batch operation.
     * @var string
     */
    protected $_code = null;
    /**
     * Explanation of the status code.
     * @var string
     */
    protected $_reason = null;
    /**
     * The MIME type of the data contained in this <batch:status> element.
     * @var string
     */
    protected $_contentType = null;

    /**
     * Constructor for Zend_Gdata_Batch_Status class.
     * @param string $code The status code
     * @param string $reason Explanation of the response.
     * @param string $contentType The MIME type of the response
     * @param string $body The contents of the response (only available if
     * the MIME type is specified).
     * @return void
     */
    public function __construct($code = null, $reason = null,
        $contentType = null)
    {
        parent::__construct();
        $this->_code = $code;
        $this->_reason = $reason;
        $this->_contentType = $contentType;
    }

    /**
     * Needed for getXML() to transfer properties to XML.
     * @see library/Zend/Gdata/App/Zend_Gdata_App_Base::getDOM()
     */
    public function getDOM($doc=null, $majorVersion=1, $minorVersion=null)
    {
        $element = parent::getDOM($doc, $majorVersion, $minorVersion);
        if ($this->_code !== null) {
            $element->setAttribute('code', $this->_code);
        }
        if ($this->_reason !== null) {
            $element->setAttribute('reason', $this->_reason);
        }
        if ($this->_contentType !== null) {
            $element->setAttribute('content-type', $this->_contentType);
        }
        return $element;
    }

    protected function takeAttributeFromDOM($attribute)
    {
        switch ($attribute->localName) {
            case 'code':
                $this->_code = $attribute->nodeValue;
                break;
            case 'reason':
                $this->_reason = $attribute->nodeValue;
                break;
            case 'content-type':
                $this->_contentType = $attribute->nodeValue;
                break;
            default:
                parent::takeAttributeFromDOM($attribute);
        }
    }

    public function __toString()
    {
        return $this->getCode() . ': ' . $this->getReason();
    }

    public function getCode()
    {
        return $this->_code;
    }

    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }

    public function getReason()
    {
        return $this->_reason;
    }

    public function setReason($reason)
    {
        $this->_reason = $reason;
        return $this;
    }

    public function getContentType()
    {
        return $this->_contentType;
    }

    public function setContentType($contentType)
    {
        $this->_contentType = $contentType;
        return $this;
    }
}
