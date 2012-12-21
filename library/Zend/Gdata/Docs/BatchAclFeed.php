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
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */

/**
 * @see Zend_Gdata_Feed
 */
require_once 'Zend/Gdata/Docs/AclFeed.php';

/**
 * @see Zend_Gdata_Docs_AclEntry
 */
require_once 'Zend/Gdata/Docs/BatchAclEntry.php';

/**
 * Data model class for a Google Docs ACL Feed for batch processing
 *
 * @category   Zend
 * @package    Zend_Gdata
 * @subpackage Docs
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Gdata_Docs_BatchAclFeed extends Zend_Gdata_Docs_AclFeed
{
    /**
     * The classname for individual feed elements.
     * @var string
     */
    protected $_entryClassName = 'Zend_Gdata_Docs_BatchAclEntry';

    /**
     * The classname for the feed.
     * @var string
     */
    protected $_feedClassName = 'Zend_Gdata_Docs_BatchAclFeed';

    /**
     * Create a new instance of a feed representing a document ACL.
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
}
