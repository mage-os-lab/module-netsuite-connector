<?php
//phpcs:ignoreFile
use Magento\Sales\Model\Order\Payment;

// @codingStandardsIgnoreFile

require 'default_rollback.php';
require 'product_simple.php';
/** @var \Magento\Catalog\Model\Product $product */

$addressData = include __DIR__ . '/address_data.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$billingAddress = $objectManager->create('Magento\Sales\Model\Order\Address', ['data' => $addressData]);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

/** @var Payment $payment */
$payment = $objectManager->create(Payment::class);
$payment->setMethod('checkmo')
    ->setAdditionalInformation([
        'token_metadata' => [
            'token' => 'f34vjw',
            'customer_id' => 1
        ]
    ]);

/** @var \Magento\Sales\Model\Order\Item $orderItem */
$orderItem = $objectManager->create('Magento\Sales\Model\Order\Item');
$orderItem->setProductId($product->getId())->setQtyOrdered(2);
$orderItem->setBasePrice($product->getPrice());
$orderItem->setPrice($product->getPrice());
$orderItem->setRowTotal($product->getPrice());
$orderItem->setSku($product->getSku());
$orderItem->setWeight($product->getWeight());
$orderItem->setProductType('simple');
$orderItem->setDiscountAmount(1.25);

/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->create('Magento\Sales\Model\Order');
$order->setIncrementId(
    '100000001'
)->setState(
    \Magento\Sales\Model\Order::STATE_PROCESSING
)->setStatus(
    $order->getConfig()->getStateDefaultStatus(\Magento\Sales\Model\Order::STATE_PROCESSING)
)->setSubtotal(
    100
)->setGrandTotal(
    100
)->setBaseSubtotal(
    100
)->setBaseGrandTotal(
    100
)->setCustomerIsGuest(
    true
)->setCustomerEmail(
    'customer@null.com'
)->setCustomerId(
    1
)->setBillingAddress(
    $billingAddress
)->setShippingAddress(
    $shippingAddress
)->setStoreId(
    $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId()
)->addItem(
    $orderItem
)->setPayment(
    $payment
);
$order->save();

$carrierFactory = $objectManager->create('\Magento\Shipping\Model\CarrierFactory');
$magentoShippingMethodCode = 'flatrate_flatrate';
$carrierCode = explode('_', $magentoShippingMethodCode);
$carrier = $carrierFactory->get($carrierCode[0]);
$order->setShippingMethod($magentoShippingMethodCode);
$order->setShippingDescription(
    trim($carrier->getConfigData('title') . ' - ' . $carrier->getConfigData('name'), ' -')
);

$order->save();
