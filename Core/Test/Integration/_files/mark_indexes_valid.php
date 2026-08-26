<?php
/**
 * Created by IntelliJ IDEA.
 * User: stan
 * Date: 20/11/2017
 * Time: 11:43
 */
/** @var \Magento\Framework\App\ResourceConnection $resource */

$resource = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\Framework\App\ResourceConnection::class);
$connection = $resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);

$connection->update(
    $connection->getTableName('indexer_state'),
    ['status' => 'valid']
);
