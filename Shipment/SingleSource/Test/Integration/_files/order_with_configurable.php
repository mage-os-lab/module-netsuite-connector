<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Sales\Model\Order\Payment;

// @codingStandardsIgnoreFile

require 'default_rollback.php';
require __DIR__ . '/product_configurable.php';
/** @var \Magento\Catalog\Model\Product $product */

$addressData = include __DIR__ . '/address_data.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$billingAddress = $objectManager->create(\Magento\Sales\Model\Order\Address::class, ['data' => $addressData]);
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
$orderItem->setBasePrice(10);
$orderItem->setPrice(10);
$orderItem->setRowTotal(20);
$orderItem->setSku($product->getSku());
$orderItem->setProductType('configurable');

$simple10 = $productRepository->get('simple_10');


$requestInfo = [
    'qty' => 2,
    'super_attribute' => [
        // not important
    ],
];
$orderItem->setProductOptions([
    'info_buyRequest' => $requestInfo,
    'simple_sku' => $simple10->getSku()
]);

/** @var \Magento\Sales\Model\Order\Item $orderItemSimple */
$orderItemSimple = $objectManager->create(\Magento\Sales\Model\Order\Item::class);
$orderItemSimple->setProductId($simple10->getId())->setQtyOrdered(2);
$orderItemSimple->setBasePrice($simple10->getPrice());
$orderItemSimple->setPrice($simple10->getPrice());
$orderItemSimple->setRowTotal($simple10->getPrice());
$orderItemSimple->setSku($simple10->getSku());
$orderItemSimple->setProductType('simple');
$orderItemSimple->setWeight($simple10->getWeight());
$orderItemSimple->setParentItem($orderItem);

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
)->setBillingAddress(
    $billingAddress
)->setShippingAddress(
    $shippingAddress
)->setStoreId(
    $objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore()->getId()
)->addItem(
    $orderItem
)->addItem(
    $orderItemSimple
)->setPayment(
    $payment
);
$order->save();
