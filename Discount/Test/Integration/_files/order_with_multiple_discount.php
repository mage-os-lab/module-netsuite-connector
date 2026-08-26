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
/** @var \Magento\SalesRule\Model\Rule $rule */
$rule = $objectManager->create(\Magento\SalesRule\Model\Rule::class);
$rule->setName('Integration Rule 0.25 OFF');
$rule->setId(1);
$rule->save();

/** @var \Magento\SalesRule\Model\Rule $rule */
$rule = $objectManager->create(\Magento\SalesRule\Model\Rule::class);
$rule->setName('Integration Rule 1.00 OFF');
$rule->setId(2);
$rule->save();


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
$orderItem->setAppliedRuleIds('1,2');


/** @var \Magento\Sales\Model\Order\Item $orderItem2 */
$orderItem2 = $objectManager->create('Magento\Sales\Model\Order\Item');
$orderItem2->setProductId($product->getId())->setQtyOrdered(2);
$orderItem2->setBasePrice($product->getPrice());
$orderItem2->setPrice($product->getPrice());
$orderItem2->setRowTotal($product->getPrice());
$orderItem2->setSku($product->getSku());
$orderItem2->setWeight($product->getWeight());
$orderItem2->setProductType('simple');
$orderItem2->setDiscountAmount(0.25);
$orderItem2->setAppliedRuleIds('1');


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
)->setDiscountAmount(
    1.50
)->setCouponCode(
    'Integration Coupon'
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
)->addItem(
$orderItem2
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
