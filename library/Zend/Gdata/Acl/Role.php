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
 * @subpackage Acl
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see Zend_Gdata_App_Base
 */
require_once 'Zend/Gdata/App/Base.php';

/**
 * Data model class for a Google ACL Role entry.
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Acl
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Gdata_Acl_Role extends Zend_Gdata_App_Base
{
    /**
     * The role for the user.
     * @var string
     */
    protected $_value;

    protected $_entryClassName = 'Zend_Gdata_Acl_Role';

    protected $_rootNamespace = 'gAcl';

    protected $_rootElement = 'role';

    /**
     * Creates an instance of Zend_Gdata_Acl_Role.
     * @param string $roleValue The value of the role. Accepts 'owner',
     * 'writer' and 'reader'.
     * @return void
     */
    public function __construct($roleValue=null)
    {
        // NOTE: namespaces must be registered before calling parent
        $this->registerNamespace('gAcl', 'http://schemas.google.com/acl/2007');
        if ($roleValue !== null) {
            $this->setValue($roleValue);
        }
        parent::__construct();
    }

    /**
     * Retrieves a DOMElement which corresponds to this element and all
     * child properties.  This is used to build an entry back into a DOM
     * and eventually XML text for sending to the server upon updates, or
     * for application storage/persistence.
     *
     * @param DOMDocument $doc The DOMDocument used to construct DOMElements
     * @return DOMElement The DOMElement representing this element and all
     * child properties.
     */
    public function getDOM($doc = null, $majorVersion = 1, $minorVersion = null)
    {
        $element = parent::getDOM($doc, $majorVersion, $minorVersion);
        if ($this->_value !== null) {
            $element->setAttribute('value', $this->_value);
        }
        return $element;
    }

    /**
     * Given a DOMNode representing an attribute, tries to map the data into
     * instance members. Here the mapping takes place on the 'value' attribute.
     *
     * @param DOMNode $attribute The DOMNode attribute needed to be handled
     */
    protected function takeAttributeFromDOM($attribute)
    {
        switch ($attribute->localName) {
            case 'value':
                $this->_value = $attribute->nodeValue;
                break;
            default:
                parent::takeAttributeFromDOM($attribute);
        }
    }

    /**
     * Sets the value attribute.
     * @param string $value The value of the role type. Accepts 'owner',
     * 'writer' and 'reader'.
     * @return Zend_Gdata_Acl_Role Provides a fluent interface.
     */
    public function setValue($value)
    {
        $this->_value = $value;
        return $this;
    }

    /**
     * Gets the value of the role.
     * @return string
     */
    public function getValue()
    {
        return $this->_value;
    }
}
