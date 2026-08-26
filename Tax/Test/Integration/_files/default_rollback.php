<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
// phpcs:disable

use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get('Magento\Framework\Registry');
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var $order \Magento\Sales\Model\Order */
$orderCollection = $objectManager->create('Magento\Sales\Model\ResourceModel\Order\Collection');
foreach ($orderCollection as $order) {
    $order->delete();
}

/** @var $product \Magento\Catalog\Model\Product */
$productCollection = Bootstrap::getObjectManager()->create('Magento\Catalog\Model\ResourceModel\Product\Collection');
foreach ($productCollection as $product) {
    $product->delete();
}

/** @var \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder */
$searchCriteriaBuilder = $objectManager->get(\Magento\Framework\Api\SearchCriteriaBuilder::class);

/** @var CreditmemoRepositoryInterface $cmRepository */
$cmRepository = $objectManager->get(CreditmemoRepositoryInterface::class);

/*
$searchCriteria = $searchCriteriaBuilder
    ->addFilter('netsuite_internal_id', '1001')
    ->create();

$magentoCMs = $cmRepository->getList($searchCriteria)->getItems();
foreach ($magentoCMs as $creditMemo) {
    $cmRepository->delete($creditMemo);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
*/
