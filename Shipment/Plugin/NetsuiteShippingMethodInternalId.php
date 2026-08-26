<?php declare(strict_types=1);
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


namespace MageOS\NetSuiteConnector\Shipment\Plugin;

/**
 * This class adds ability to use shipping methods in nsc/order and reverse dependencies
 */
class NetsuiteShippingMethodInternalId
{
    private \MageOS\NetSuiteConnector\Shipment\Model\Config\ShippingConfig $shippingConfig;

    /**
     * @param \MageOS\NetSuiteConnector\Shipment\Model\Config\ShippingConfig $shippingConfig
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Shipment\Model\Config\ShippingConfig $shippingConfig
    ) {
        $this->shippingConfig = $shippingConfig;
    }

    /**
     * Get Netsuite Shipping Method Internal Id
     *
     * @param \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Shipment $subject
     * @param mixed $result
     * @param string $magentoShippingMethodCode
     * @return mixed
     * @SuppressWarnings("unused")
     */
    public function afterGetNetsuiteShippingMethodInternalId(
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Shipment $subject,
        int $result,
        $magentoShippingMethodCode
    ): int {
        $shippingMapping = $this->shippingConfig->getNetsuiteMapping();
        foreach ($shippingMapping as $shippingMappingElement) {
            if ($shippingMappingElement['shipping_method'] == $magentoShippingMethodCode) {
                return (int)$shippingMappingElement['internal_netsuite_id'];
            }
        }
        return (int)$this->shippingConfig->getNetsuiteDefaultShippingId();
    }
}
