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

//phpcs:ignoreFile
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

/** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManager */
$messageManager = $objectManager->create(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
$searchCriteriaBuilder = $objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilder::class);
$monitorRepository = $objectManager->create(\MageOS\NetSuiteConnector\Core\Api\MonitorRegistryInterface::class);
$searchCriteria = $searchCriteriaBuilder->addFilter('item_id', '1111', 'eq')->create();
$messageItems = $monitorRepository->getList($searchCriteria);
if ($messageItems->getTotalCount()) {
    foreach ($messageItems->getItems() as $message) {
        $messageManager->deleteById($message->getId());
        $monitorRepository->delete($message);
    }
}

//REMOVING PRODUCT
/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get('Magento\Framework\Registry');

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get('Magento\Catalog\Api\ProductRepositoryInterface');
try {
    $product = $productRepository->get('TestConfSimple', false, null, true);
    $productRepository->delete($product);
} catch (NoSuchEntityException $e) {
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
