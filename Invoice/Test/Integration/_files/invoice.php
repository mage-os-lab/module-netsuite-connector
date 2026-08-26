<?php

/** @var \Magento\Sales\Model\Order $order */
$order = \Magento\TestFramework\ObjectManager::getInstance()->create(\Magento\Sales\Model\Order::class);
$order->loadByIncrementId('100000001');

$registry = \Magento\TestFramework\ObjectManager::getInstance()->get(
    \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry::class
);
$registry->register('netsuite_skip_invoice_export', true);

$orderService = \Magento\TestFramework\ObjectManager::getInstance()->create(
    \Magento\Sales\Api\InvoiceManagementInterface::class
);
$invoice = $orderService->prepareInvoice($order);
$invoice->register();
$order = $invoice->getOrder();
$order->setIsInProcess(true);
$transactionSave = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create(\Magento\Framework\DB\Transaction::class);
$transactionSave->addObject($invoice)->addObject($order)->save();
