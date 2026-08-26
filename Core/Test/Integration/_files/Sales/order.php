<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Sales\Model\Order\Payment;

// @codingStandardsIgnoreFile

require __DIR__ .'/../default_rollback.php';
require __DIR__ .'/../Products/product_simple.php';
/** @var \Magento\Catalog\Model\Product $product */

$addressData = include __DIR__ . '/../address_data.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$billingAddress = $objectManager->create('Magento\Sales\Model\Order\Address', ['data' => $addressData]);
$billingAddress->setAddressType('billing');

$shippingAddress = clone $billingAddress;
$shippingAddress->setId(null)->setAddressType('shipping');

/** @var Payment $payment */
$payment = $objectManager->create(Payment::class);

try{
    $payment->setMethod('checkmo')
        ->setAdditionalInformation([
            'token_metadata' => [
                'token' => 'f34vjw',
                'customer_id' => 1
            ]
        ]);
}catch (\Exception $e)
{
    echo $e->getTraceAsString() . "\n";
}



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

$orderNumber = '10'.rand(100000,9999999);

/** @var \Magento\Sales\Model\Order $order */
$order = $objectManager->create('Magento\Sales\Model\Order');
$order->setIncrementId(
    $orderNumber//'100000001'
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
)->setCustomerEmail(
    'customer-general@rocketweb.com'
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
)->setNetsuiteInternalId($orderNumber);

try{
    $order->save();
}catch (Exception $e)
{
    echo $e->getTraceAsString() . "\n";
}

require __DIR__ .'/quote.php';
require __DIR__ .'/submit_order_to_ns_queue.php';
