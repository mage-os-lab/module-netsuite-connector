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


namespace MageOS\NetSuiteConnector\Tax\Plugin;

use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\SalesOrder;
use MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Shipment;

class OrderShipmentAddTax
{
    private \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig;
    private \MageOS\NetSuiteConnector\Tax\Model\Tax $tax;

    public function __construct(
        \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig,
        \MageOS\NetSuiteConnector\Tax\Model\Tax $tax
    ) {
        $this->taxConfig = $taxConfig;
        $this->tax = $tax;
    }

    /**
     * @param Shipment $subject
     * @param $result
     * @param SalesOrder $netsuiteOrder
     * @param OrderInterface $magentoOrder
     * @return null
     *
     * @suppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterAddShipment(
        Shipment $subject,
        $result,
        SalesOrder $netsuiteOrder,
        OrderInterface $magentoOrder
    ) {
        if ($this->taxConfig->getSkipTax()) {
            return null;
        }

        /**
         * No shipping method set, do not apply tax
         */
        if (!$netsuiteOrder->shipMethod) {
            return null;
        }

        $this->tax->getOrderExportTaxManager()->addShippingTax($netsuiteOrder);

        return null;
    }
}
