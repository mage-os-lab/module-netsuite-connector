<?php
/**
 * Created by IntelliJ IDEA.
 * User: stan
 * Date: 20/11/2017
 * Time: 11:35
 */

use Magento\Framework\Event\ManagerInterface as EventManager;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Framework\App\ResourceConnection $resource */
$resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection();
$connection->delete($resource->getTableName('mageos_netsuite_monitor'));
$connection->delete($resource->getTableName('mageos_netsuite_message'));

$order = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->create('Magento\Sales\Model\Order');
$order->loadByIncrementId('100000001');

$quote = $objectManager->create('Magento\Quote\Model\Quote')->load(1);

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
