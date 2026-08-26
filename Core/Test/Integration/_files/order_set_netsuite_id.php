<?php
/**
 * Created by IntelliJ IDEA.
 * User: stan
 * Date: 20/11/2017
 * Time: 14:16
 */

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

$order = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create('Magento\Sales\Model\Order');
$order->loadByIncrementId('100000001');
$order->setNetsuiteInternalId('1');
$order->save();
