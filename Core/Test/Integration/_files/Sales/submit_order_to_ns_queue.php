<?php
/**
 * Created by IntelliJ IDEA.
 * User: stan
 * Date: 20/11/2017
 * Time: 11:35
 */



use Magento\Framework\Event\ManagerInterface as EventManager;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();


//global $orderNumber, $quote;
//
//$order = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
//    ->create('Magento\Sales\Model\Order');
//$order->loadByIncrementId($orderNumber);

//$quote = $objectManager->create('Magento\Quote\Model\Quote')->load(1);

/** @var \Magento\CatalogInventory\Observer\ItemsForReindex $itemsForReindex */
$itemsForReindex = $objectManager->get(\Magento\CatalogInventory\Observer\ItemsForReindex::class);
$itemsForReindex->setItems([]);

$eventManager = $objectManager->get(EventManager::class);

$eventManager->dispatch(
    'sales_model_service_quote_submit_success',
    [
        'order' => $order,
        'quote' => $quote
    ]
);









//use Magento\Framework\Event\ManagerInterface as EventManager;
//
//$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
//
//$order = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
//    ->create('Magento\Sales\Model\Order');
//$order->loadByIncrementId('100000001');
//
//$quote = $objectManager->create('Magento\Quote\Model\Quote')->load(1);
//
///** @var \Magento\CatalogInventory\Observer\ItemsForReindex $itemsForReindex */
//$itemsForReindex = $objectManager->get(\Magento\CatalogInventory\Observer\ItemsForReindex::class);
//$itemsForReindex->setItems([]);
//
//$eventManager = $objectManager->get(EventManager::class);
//
//$eventManager->dispatch(
//    'sales_model_service_quote_submit_success',
//    [
//        'order' => $order,
//        'quote' => $quote
//    ]
//);
