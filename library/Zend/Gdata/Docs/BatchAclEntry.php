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
 * @see Zend_Gdata_Entry
 */
require_once 'Zend/Gdata/Entry.php';

/**
 * @see Zend_Gdata_Acl_Role
 */
require_once 'Zend/Gdata/Acl/Role.php';

/**
 * @see Zend_Gdata_Acl_Scope
 */
require_once 'Zend/Gdata/Acl/Scope.php';

/**
 * @see Zend_Gdata_Batch_Id
 */
require_once 'Zend/Gdata/Batch/Id.php';

/**
 * @see Zend_Gdata_Batch_Operation
 */
require_once 'Zend/Gdata/Batch/Operation.php';

/**
 * @see Zend_Gdata_Batch_Status
 */
require_once 'Zend/Gdata/Batch/Status.php';

/**
 * Data model class for a Google Docs ACL entry for batch processing.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Docs
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Gdata_Docs_BatchAclEntry extends Zend_Gdata_Docs_AclEntry
{
    protected $_entryClassName = 'Zend_Gdata_Docs_BatchAclEntry';

    /**
     * The batch ID needed for insert operations during batch processing
     * @var Zend_Gdata_Batch_Id
     */
    protected $_batchId;

    /**
     * The batch operation type.
     * @var Zend_Gdata_Batch_Operation
     */
    protected $_batchOperation;

    /**
     * The batch response status
     * @var Zend_Gdata_Batch_Status
     */
    protected $_batchStatus;

    /**
     * Create a new instance of an entry representing a document ACL.
     *
     * @param DOMElement $element (optional) DOMElement from which this
     *          object should be constructed.
     */
    public function __construct($element = null)
    {
        $this->registerNamespace(
            Zend_Gdata_Batch::$namespaces[0][0],
            Zend_Gdata_Batch::$namespaces[0][1],
            Zend_Gdata_Batch::$namespaces[0][2],
            Zend_Gdata_Batch::$namespaces[0][3]
        );
        // NOTE: namespaces must be registered before calling parent
        parent::__construct($element);
    }

    public function getDOM($doc=null, $majorVersion=1, $minorVersion=null)
    {
        $element = parent::getDOM($doc, $majorVersion, $minorVersion);
        if ($this->_batchId !== null) {
            $element->appendChild($this->_batchId->getDOM($element->ownerDocument, $majorVersion, $minorVersion));
        }
        if ($this->_batchOperation !== null) {
            $element->appendChild($this->_batchOperation->getDOM($element->ownerDocument, $majorVersion, $minorVersion));
        }
        if ($this->_batchStatus !== null) {
            $element->appendChild($this->_batchStatus->getDOM($element->ownerDocument, $majorVersion, $minorVersion));
        }
        return $element;
    }

    protected function takeChildFromDOM($child)
    {
        $absoluteNodeName = $child->namespaceURI . ':' . $child->localName;
        switch ($absoluteNodeName) {
            case $this->lookupNamespace('batch') . ':id':
                $batchId = new Zend_Gdata_Batch_Id();
                $batchId->transferFromDOM($child);
                $this->_batchId = $batchId;
                break;
            case $this->lookupNamespace('batch') . ':operation':
                $batchOperation = new Zend_Gdata_Batch_Operation();
                $batchOperation->transferFromDOM($child);
                $this->_batchOperation = $batchOperation;
                break;
            case $this->lookupNamespace('batch') . ':status':
                $batchStatus = new Zend_Gdata_Batch_Status();
                $batchStatus->transferFromDOM($child);
                $this->_batchStatus = $batchStatus;
                break;
            default:
                parent::takeChildFromDOM($child);
                break;
        }
    }

    /**
     * Gets the batch ID of this batch processing entry.
     * @return Zend_Gdata_Batch_Id
     */
    public function getBatchId()
    {
        return $this->_batchId;
    }

    /**
     * Sets the batch ID of this batch processing entry.
     * @param Zend_Gdata_Batch_Id $batchId
     * @return Zend_Gdata_Docs_BatchAclEntry Provides a fluent interface.
     */
    public function setBatchId($batchId)
    {
        $this->_batchId = $batchId;
        return $this;
    }

    /**
     * Gets the batch operation instance of this entry.
     * @return Zend_Gdata_Batch_Operation
     */
    public function getBatchOperation()
    {
        return $this->_batchOperation;
    }

    /**
     * Sets the batch operation instance for this entry.
     * @param Zend_Gdata_Batch_Operation $batchOperation
     * @return Zend_Gdata_Docs_BatchAclEntry
     */
    public function setBatchOperation($batchOperation)
    {
        $this->_batchOperation = $batchOperation;
        return $this;
    }

    /**
     * Gets the batch status instance of this entry.
     * @return Zend_Gdata_Batch_Status
     */
    public function getBatchStatus()
    {
        return $this->_batchStatus;
    }

    /**
     * Sets the batch status instance for this entry.
     * @param Zend_Gdata_Batch_Status $batchStatus
     * @return Zend_Gdata_Docs_BatchAclEntry
     */
    public function setBatchStatus($batchStatus)
    {
        $this->_batchStatus = $batchStatus;
        return $this;
    }
}
