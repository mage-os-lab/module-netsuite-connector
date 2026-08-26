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

namespace MageOS\NetSuiteConnector\Order\Model\Mapper;

use Magento\Sales\Api\Data\OrderInterface;
use NetSuite\Classes\SalesOrder;

/**
 * This class creates a magento order from saleOrder object retrieved from NS
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Order
{
    private \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig $salesConfig;
    private \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Customer $nsCustomer;
    private \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItemList $nsOrderItemList;
    private \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItem $nsOrderItem;
    private \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Addresses $nsAddresses;
    private \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Shipment $nsShipment;
    private \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Payment $nsPayment;
    private \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\CustomFields $nsCustomFields;

    public function __construct(
        \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig $salesConfig,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Customer $nsCustomer,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItemList $nsOrderItemList,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\OrderItem $nsOrderItem,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Addresses $nsAddresses,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Shipment $nsShipment,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Payment $nsPayment,
        \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\CustomFields $nsCustomFields,
        private readonly \MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport\Location $nsLocation
    ) {
        $this->salesConfig = $salesConfig;
        $this->nsCustomer = $nsCustomer;
        $this->nsOrderItemList = $nsOrderItemList;
        $this->nsOrderItem = $nsOrderItem;
        $this->nsAddresses = $nsAddresses;
        $this->nsShipment = $nsShipment;
        $this->nsPayment = $nsPayment;
        $this->nsCustomFields = $nsCustomFields;
    }

    /**
     * Create NS order from magento order
     *
     * @param OrderInterface $magentoOrder
     * @return SalesOrder
     * @throws \Exception
     */
    public function getNetsuiteFormat(OrderInterface $magentoOrder): SalesOrder
    {
        $netsuiteOrder = new SalesOrder();
        $netsuiteOrder->tranDate = $this->getOrderDateFormatted($magentoOrder);

        //Set customer record
        $this->nsCustomer->addCustomer($netsuiteOrder, $magentoOrder);

        // todo: refactor config system to be able to use non-default scope
        // $magentoOrder->getStore()->getWebsiteId()
        $netsuiteOrder->orderStatus = $this->salesConfig->getDefaultOrderStatus();

        $this->nsOrderItemList->initOrderItemList($netsuiteOrder);
        foreach ($magentoOrder->getItems() as $item) {
            if ($this->nsOrderItem->shouldSkipOrderItem($item, $magentoOrder)) {
                continue;
            }
            $this->nsOrderItem->addOrderItem($item, $netsuiteOrder, $magentoOrder);
        }

        $this->nsAddresses->addAddresses($netsuiteOrder, $magentoOrder);
        $this->nsShipment->addShipment($netsuiteOrder, $magentoOrder);
        $this->nsPayment->addPayment($netsuiteOrder, $magentoOrder);
        $this->nsCustomFields->addCustomFields($netsuiteOrder, $magentoOrder);
        $this->nsLocation->addLocation($netsuiteOrder);
        return $netsuiteOrder;
    }

    /**
     * Get formatted order data
     *
     * @param OrderInterface $magentoOrder
     * @return string
     * @throws \Exception
     */
    private function getOrderDateFormatted(OrderInterface $magentoOrder): string
    {
        $orderDate = new \DateTime($magentoOrder->getCreatedAt());
        return $orderDate->format(\DateTime::ISO8601);
    }
}
