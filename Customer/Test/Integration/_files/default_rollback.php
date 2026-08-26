<?php
/**
 * RocketWeb
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 *
 *
 */


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

$searchCriteria = $searchCriteriaBuilder
    ->addFilter('netsuite_internal_id', '1001')
    ->create();

$magentoCMs = $cmRepository->getList($searchCriteria)->getItems();
foreach ($magentoCMs as $creditMemo) {
    $cmRepository->delete($creditMemo);
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
