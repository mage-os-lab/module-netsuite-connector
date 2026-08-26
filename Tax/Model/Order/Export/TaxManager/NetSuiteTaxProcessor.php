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
 *
 */
declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManager;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\CustomFieldList;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SalesOrderItem;
use NetSuite\Classes\StringCustomFieldRef;
use MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManagerInterface;

/**
 * NetSuiteTaxProcessor - tax manager that implements next logic for the Magento Order tax handling:
 * # taxes are processed on the NetSuite side
 * # connector collects tax info
 * # connector adds info to the special fields in the Sales Order
 */
class NetSuiteTaxProcessor implements TaxManagerInterface
{
    /**
     * @var \MageOS\NetSuiteConnector\Tax\Model\Config\Tax
     */
    private $taxConfig;

    /**
     * @var float
     */
    private $taxAmount = 0.0;
    /**
     * @var \MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManager\TaxCalculation\TaxPerItem
     */
    private $taxPerItem;
    /**
     * @var  \MageOS\NetSuiteConnector\Core\Model\Logger\Logger
     */
    private $logger;

    /**
     * NetSuiteSide constructor.
     * @param \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig
     * @param \MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManager\TaxCalculation\TaxPerItem $taxPerItem
     * @param \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Tax\Model\Config\Tax $taxConfig,
        \MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManager\TaxCalculation\TaxPerItem $taxPerItem,
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger
    ) {
        $this->taxConfig = $taxConfig;
        $this->taxPerItem = $taxPerItem;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function collectOrderItemTax(
        OrderItemInterface $item,
        ProductInterface $product,
        SalesOrderItem $netsuiteOrderItem
    ): void {
        $this->taxAmount += $this->taxPerItem->getTaxAmount($item, $product);
        $isTaxable = $product->getData('tax_class_id');
        if (!empty($isTaxable)) {
            $netsuiteOrderItem->isTaxable = true;
        }
    }

    /**
     * @inheritDoc
     */
    // phpcs:ignore
    public function addShippingTax(SalesOrder $netsuiteOrder): void
    {
        //skipped for now see NC-418 comments
    }

    /**
     * @inheritDoc
     */
    public function addTax(SalesOrder $netsuiteOrder, OrderInterface $magentoOrder, $taxAmount = null): void
    {
        if (null === $taxAmount) {
            $taxAmount = $this->taxAmount;
        }
        //tax setting
        $taxAmount += $magentoOrder->getShippingTaxAmount();
        $taxAmount = round($taxAmount, 2);
        if ($taxAmount > 0) {
            $netsuiteOrder->isTaxable = true;
        }
        $this->addCustomFields($netsuiteOrder, (float)$magentoOrder->getGrandTotal(), (float)$taxAmount??0.00);
        $this->taxAmount = 0;
    }

    /**
     * method sets tax amount and total amount of the order to custom fields
     * @param SalesOrder $netsuiteOrder
     * @param float $orderTotal
     * @param float $taxAmount
     */
    private function addCustomFields(SalesOrder $netsuiteOrder, float $orderTotal, float $taxAmount): void
    {
        if (empty($this->taxConfig->getSalesOrderTotalAmountId())) {
            $this->logger->debug(sprintf(
                'There was no Custom Field for Total Amount set! ' .
                'Order have been sent without this field. Total Amount was %s.',
                $orderTotal
            ));
        }
        if (empty($this->taxConfig->getSalesOrderTaxAmountId())) {
            $this->logger->debug(sprintf(
                'There was no Custom Field for Tax Amount set! ' .
                'Order have been sent without this field. Tax Amount was %s.',
                $taxAmount
            ));
        }
        if (empty($this->taxConfig->getSalesOrderTotalAmountId()) &&
            empty($this->taxConfig->getSalesOrderTaxAmountId())) {
            return;
        }
        $customFields = [];
        //tax amount
        if (!empty($this->taxConfig->getSalesOrderTaxAmountId())) {
            $customFieldTax = new StringCustomFieldRef();
            $customFieldTax->scriptId = $this->taxConfig->getSalesOrderTaxAmountId();
            $customFieldTax->value = $taxAmount;
            $customFields[] = $customFieldTax;
        }
        //order total set
        if (!empty($this->taxConfig->getSalesOrderTotalAmountId())) {
            $customFieldTotal = new StringCustomFieldRef();
            $customFieldTotal->scriptId = $this->taxConfig->getSalesOrderTotalAmountId();
            $customFieldTotal->value = $orderTotal;
            $customFields[] = $customFieldTotal;
        }
        //collect CustomFieldList object
        if ($netsuiteOrder->customFieldList === null) {
            $netsuiteOrder->customFieldList = new CustomFieldList();
            $netsuiteOrder->customFieldList->customField = [];
        }
        $netsuiteOrder->customFieldList->customField = array_merge(
            $netsuiteOrder->customFieldList->customField,
            $customFields
        );
    }
}
