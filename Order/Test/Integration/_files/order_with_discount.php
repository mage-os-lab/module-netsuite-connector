<?php
/**
 * Created by IntelliJ IDEA.
 * User: stan
 * Date: 20/11/2017
 * Time: 14:16
 */

require __DIR__ . '/order.php';

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$order = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create('Magento\Sales\Model\Order');
$order->loadByIncrementId('100000001');
$order->setDiscountAmount(1.25);
$order->save();
