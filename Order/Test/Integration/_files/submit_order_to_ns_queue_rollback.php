<?php

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Framework\App\ResourceConnection $resource */
$resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection();

$connection->delete($resource->getTableName('mageos_netsuite_monitor'));
$connection->delete($resource->getTableName('mageos_netsuite_message'));
