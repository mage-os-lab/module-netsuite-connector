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

namespace MageOS\NetSuiteConnector\Invoice\Model\Mapper;

use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use NetSuite\Classes\CashSale;
use NetSuite\Classes\Customer;
use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException;
use MageOS\NetSuiteConnector\Core\Exception\SkipRecordException;

/**
 * This class creates a magento invoice from cashSale object retrieved from NS
 */
class ImportInvoice
{
    /**
     * @var \Magento\Sales\Api\Data\InvoiceInterfaceFactory
     */
    private $invoiceFactory;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface
     */
    private $orderRegistry;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Helper\MapperCustomer
     */
    private $customerMapperHelper;

    /**
     * @var \Magento\Framework\DataObject\Copy
     */
    private $objectCopyService;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Helper\MapperAddress
     */
    private $addressMapperHelper;

    /**
     * @var \Magento\Sales\Api\OrderAddressRepositoryInterface
     */
    private $orderAddressRepository;

    /**
     * @var \Magento\Sales\Api\Data\InvoiceItemInterfaceFactory
     */
    private $invoiceItemFactory;

    /**
     * @var \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig
     */
    private $salesConfig;

    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository
     */
    private $netsuiteProductRepository;

    /**
     * ImportInvoice constructor.
     * @param \Magento\Sales\Api\Data\InvoiceInterfaceFactory $invoiceFactory
     * @param \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry
     * @param \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapperHelper
     * @param \Magento\Framework\DataObject\Copy $objectCopyService
     * @param \MageOS\NetSuiteConnector\Customer\Model\Mapper\Address $addressMapperHelper
     * @param \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository
     * @param \Magento\Sales\Api\Data\InvoiceItemInterfaceFactory $invoiceItemFactory
     * @param \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig $salesConfig
     * @param \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository
     */
    public function __construct(
        \Magento\Sales\Api\Data\InvoiceInterfaceFactory $invoiceFactory,
        \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry,
        \MageOS\NetSuiteConnector\Customer\Model\Mapper\Customer $customerMapperHelper,
        \Magento\Framework\DataObject\Copy $objectCopyService,
        \MageOS\NetSuiteConnector\Customer\Model\Mapper\Address $addressMapperHelper,
        \Magento\Sales\Api\OrderAddressRepositoryInterface $orderAddressRepository,
        \Magento\Sales\Api\Data\InvoiceItemInterfaceFactory $invoiceItemFactory,
        \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig $salesConfig,
        \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository
    ) {
        $this->invoiceFactory = $invoiceFactory;
        $this->orderRegistry = $orderRegistry;
        $this->customerMapperHelper = $customerMapperHelper;
        $this->objectCopyService = $objectCopyService;
        $this->addressMapperHelper = $addressMapperHelper;
        $this->orderAddressRepository = $orderAddressRepository;
        $this->invoiceItemFactory = $invoiceItemFactory;
        $this->salesConfig = $salesConfig;
        $this->netsuiteProductRepository = $netsuiteProductRepository;
    }

    /**
     * Create magento invoice based on given cashSale data object (retrieved from NS)
     *
     * @param Record $cashSale
     * @return InvoiceInterface
     * @throws NetSuiteRuntimeException
     */
    public function getMagentoFormatFromCashSale(Record $cashSale): InvoiceInterface
    {
        $invoice = $this->invoiceFactory->create();

        if ($cashSale->createdFrom === null) {
            throw new SkipRecordException('CashSale has empty createdFrom');
        }

        $netsuiteOrderId = $cashSale->createdFrom->internalId;

        $magentoOrder = $this->orderRegistry->getOrderByNetSuiteId($netsuiteOrderId);
        if ($magentoOrder === null) {
            throw new NetSuiteRuntimeException(
                "Order with netsuite internal id {$cashSale->createdFrom->internalId} not found in Magento!"
            );
        }

        $netsuiteCustomer = $this->customerMapperHelper->getByInternalId($cashSale->entity->internalId);

        $invoice->setOrderId($magentoOrder->getId());
        $invoice->setStoreId($magentoOrder->getStoreId());

        $this->objectCopyService->copyFieldsetToTarget('sales_convert_order', 'to_invoice', $magentoOrder, $invoice);

        $this->addBillingAddress($cashSale, $netsuiteCustomer, $magentoOrder);

        $invoice->setBillingAddressId($magentoOrder->getBillingAddressId());
        $invoice->setShippingAddressId($magentoOrder->getShippingAddressId());

        $this->addInvoiceItems($cashSale, $invoice, $magentoOrder);

        $discount = $this->getDiscountAmount($cashSale);
        $tax = $this->getTaxAmount($cashSale);
        $shipping = $this->getShippingAmount($cashSale);

        $invoice->setDiscountAmount(-$discount)->setBaseDiscountAmount(-$discount);
        $invoice->setTaxAmount($tax)->setBaseTaxAmount($tax);
        $invoice->setGrandTotal($cashSale->total)->setBaseGrandTotal($cashSale->total);
        $invoice->setSubtotal($cashSale->total + $tax + $discount - $shipping);
        $invoice->setBaseSubtotal($cashSale->total + $tax + $discount - $shipping);
        $invoice->setShippingAmount($shipping)->setBaseShippingAmount($shipping);

        $invoice->setState(\Magento\Sales\Model\Order\Invoice::STATE_PAID);

        return $invoice;
    }

    /**
     * Create and add billing address based on cashSale data from NS
     *
     * @param Record $cashSale
     * @param Customer $netsuiteCustomer
     * @param OrderInterface $magentoOrder
     */
    private function addBillingAddress(Record $cashSale, $netsuiteCustomer, $magentoOrder)
    {
        if ($cashSale->billingAddress) {
            $magentoBillingAddress = $this->addressMapperHelper->getAddressMagentoFormatFromNetsuiteAddress(
                $cashSale->billingAddress,
                $netsuiteCustomer,
                $magentoOrder
            );
            $magentoBillingAddress->setAddressType('billing');
            $magentoBillingAddress->setId($magentoOrder->getBillingAddressId());
            $this->orderAddressRepository->save($magentoBillingAddress);
        }
    }

    /**
     * Add invoice items based on cashSale data from NS
     *
     * @param Record $cashSale
     * @param InvoiceInterface $invoice
     * @param OrderInterface $magentoOrder
     */
    private function addInvoiceItems(Record $cashSale, $invoice, $magentoOrder)
    {
        $itemMap = $this->prepareItemMap($cashSale, $magentoOrder);

        $totalQuantity = 0;
        foreach ($itemMap as $item) {
            $parentOrderItem = $item['magento_orderitem_parent_object'];
            $quantity = $item['netsuite_object']->quantity;

            if ($parentOrderItem) {
                // Configurable products
                // we need to use price from order item instead because there are times when we don't get
                // all the items from NetSuite and the price in the rate field doesn't contain options prices
                $invoice->addItem(
                    $this->createInvoiceItem(
                        $parentOrderItem,
                        $quantity,
                        $parentOrderItem->getPrice(),
                        $item['netsuite_object']->taxRate1
                    )
                );

                $invoice->addItem(
                    $this->createInvoiceItem(
                        $item['magento_orderitem_object'],
                        $quantity,
                        0,
                        0
                    )
                );

            } else {
                // Simple products
                $invoice->addItem(
                    $this->createInvoiceItem(
                        $item['magento_orderitem_object'],
                        $item['netsuite_object']->quantity,
                        $item['netsuite_object']->rate,
                        $item['netsuite_object']->taxRate1
                    )
                );
            }

            $totalQuantity += $quantity;
        }

        $invoice->setTotalQty($totalQuantity);
    }

    /**
     * Prepare order items data for adding to invoice
     *
     * @param Record $cashSale
     * @param OrderInterface $magentoOrder
     * @return array
     */
    private function prepareItemMap(Record $cashSale, $magentoOrder): array
    {
        $itemMap = [];
        foreach ($cashSale->itemList->item as $netsuiteItem) {
            foreach ($magentoOrder->getAllItems() as $magentoOrderItem) {
                if (!$this->netsuiteProductRepository->isEqual($netsuiteItem->item, $magentoOrderItem)) {
                    continue;
                }
                $item = [];
                $item['netsuite_object'] = $netsuiteItem;
                $item['magento_orderitem_object'] = $magentoOrderItem;
                $item['magento_orderitem_parent_object'] = $this->getOrderItemParent($magentoOrder, $magentoOrderItem);
                $itemMap[] = $item;
            }
        }
        return $itemMap;
    }

    /**
     * Create an invoice item for given order item
     *
     * @param OrderItemInterface $orderItemObject
     * @param float $quantity
     * @param float $rate
     * @param float $taxRate
     * @return InvoiceItemInterface
     */
    private function createInvoiceItem($orderItemObject, $quantity, $rate, $taxRate): InvoiceItemInterface
    {
        $taxAmount = round($rate * ($taxRate / 100), 2);

        $magentoInvoiceItem = $this->invoiceItemFactory->create();
        $this->objectCopyService->copyFieldsetToTarget(
            'sales_convert_order_item',
            'to_invoice_item',
            $orderItemObject,
            $magentoInvoiceItem
        );
        $magentoInvoiceItem->setOrderItem($orderItemObject);
        $magentoInvoiceItem->setProductId($orderItemObject->getProductId());

        $rowTotal = ($quantity * $rate) + $taxAmount;
        //These fields are not copied via copyFieldsetToTarget as in Magento 1, copy them manually
        if ($rate) {
            $magentoInvoiceItem->setBaseRowTotalInclTax($rowTotal)
                ->setRowTotalInclTax($rowTotal)
                ->setBaseRowTotal($rowTotal - $taxAmount)
                ->setRowTotal($rowTotal - $taxAmount)
                ->setBaseTaxAmount($taxAmount)
                ->setTaxAmount($taxAmount);
        }

        //intentionally using setData instead of ->setQty as at this point we dont care about out of stock exceptions
        $magentoInvoiceItem->setData('qty', $quantity);

        return $magentoInvoiceItem;
    }

    /**
     * Returns the parent of a specified order item. Returns null if no parent.
     *
     * @param OrderInterface $magentoOrder
     * @param OrderItemInterface $orderItem
     * @return OrderItemInterface|null
     */
    private function getOrderItemParent($magentoOrder, $orderItem)
    {
        if (!$orderItem->getParentItemId()) {
            return null;
        }
        foreach ($magentoOrder->getAllItems() as $currentOrderItem) {
            if ($currentOrderItem->getItemId() == $orderItem->getParentItemId()) {
                return $currentOrderItem;
            }
        }
        return null;
    }

    /**
     * Returns absolute discount value
     *
     * @param CashSale $cashSale
     * @return float|int
     *
     * Plugin Access
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDiscountAmount(Record $cashSale)
    {
        return 0;
    }

    /**
     * Returns absolute tax value
     *
     * @param Record $cashSale
     * @return float|int
     */
    private function getTaxAmount(Record $cashSale)
    {
        return abs($cashSale->taxTotal??0);
    }

    /**
     * Returns shipping amount
     *
     * @param Record $cashSale
     * @return float|int
     */
    private function getShippingAmount(Record $cashSale)
    {
        $shipping = $cashSale->shippingCost;
        if ((null===$shipping || !is_float($shipping)) && isset($cashSale->shipGroupList->shipGroup[0]->shippingRate)) {
            $shipping = $cashSale->shipGroupList->shipGroup[0]->shippingRate;
        }
        return $shipping;
    }
}
