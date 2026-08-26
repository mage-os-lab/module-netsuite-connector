<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 *
 */
declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Product\Model\Product\Map;

/**
 * Factory class for @see \MageOS\NetSuiteConnector\Product\Model\Product\Map\Value
 *
 * Manually created to force parameters to be correctly entered
 *
 */
class ValueFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $_instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = \MageOS\NetSuiteConnector\Product\Model\Product\Map\Value::class
    ) {
        $this->_objectManager = $objectManager;
        $this->_instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return \MageOS\NetSuiteConnector\Product\Model\Product\Map\Value
     */
    public function create(
        $magentoFieldId,
        $netsuiteFieldId,
        $netsuiteFieldType,
        $netsuiteFieldValue,
        $netsuiteListInternalId,
        array $data = []
    ) {
        $data['magentoFieldId'] = $magentoFieldId;
        $data['netsuiteFieldId'] = $netsuiteFieldId;
        $data['netsuiteFieldType'] = $netsuiteFieldType;
        $data['netsuiteFieldValue'] = $netsuiteFieldValue;
        $data['netsuiteListInternalId'] = $netsuiteListInternalId;

        return $this->_objectManager->create($this->_instanceName, $data);
    }
}
