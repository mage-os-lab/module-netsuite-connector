<?php
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

$quotes = $objectManager->create('Magento\Quote\Model\ResourceModel\Quote\Collection')->load()->getItems();

/** @var \Magento\CatalogInventory\Observer\ItemsForReindex $itemsForReindex */
$itemsForReindex = $objectManager->get(\Magento\CatalogInventory\Observer\ItemsForReindex::class);
$itemsForReindex->setItems([]);

$eventManager = $objectManager->get(EventManager::class);

$eventManager->dispatch(
    'sales_model_service_quote_submit_success',
    [
        'order' => $order,
        'quote' => reset($quotes)
    ]
);
