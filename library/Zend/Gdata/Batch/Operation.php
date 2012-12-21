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
 * Represents the batch:operation element
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Batch
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Gdata_Batch_Operation extends Zend_Gdata_Batch
{

    protected $_rootElement = 'operation';

    /**
     * The type of operation.
     * Valid values are:
     * - insert
     * - update
     * - delete
     * - query
     * @var string
     */
    protected $_type = null;

    public function __construct($type = null)
    {
        parent::__construct();
        $this->_type = $type;
    }

    public function getDOM($doc=null, $majorVersion=1, $minorVersion=null)
    {
        $element = parent::getDOM($doc, $majorVersion, $minorVersion);
        if ($this->_type !== null) {
            $element->setAttribute('type', $this->_type);
        }
        return $element;
    }

    protected function takeAttributeFromDOM($attribute)
    {
        switch ($attribute->localName) {
            case 'type':
                $this->_type = $attribute->nodeValue;
                break;
            default:
                parent::takeAttributeFromDOM($attribute);
        }
    }

    public function __toString()
    {
        return 'operation type: ' . $this->getType();
    }

    public function getType()
    {
        return $this->_type;
    }

    public function setTypeToInsert()
    {
        $this->_type = 'insert';
        return $this;
    }

    public function setTypeToUpdate()
    {
        $this->_type = 'update';
        return $this;
    }

    public function setTypeToDelete()
    {
        $this->_type = 'delete';
        return $this;
    }

    public function setTypeToQuery()
    {
        $this->_type = 'query';
        return $this;
    }

}
