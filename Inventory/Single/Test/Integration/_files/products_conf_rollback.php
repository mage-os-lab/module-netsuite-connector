<?php declare(strict_types=1);
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
 */
use Magento\Framework\Exception\NoSuchEntityException;

\Magento\TestFramework\Helper\Bootstrap::getInstance()->getInstance()->reinitialize();

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Framework\Registry $registry */
$registry = $objectManager->get('Magento\Framework\Registry');

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$productRepository = $objectManager->get('Magento\Catalog\Api\ProductRepositoryInterface');
foreach (['simple1','simple2', 'conf'] as $sku) {
    try {
        $product = $productRepository->get($sku, false, null, true);
        $productRepository->delete($product);
    } catch (NoSuchEntityException $e) {
        // No action needed
    }
}
/** @var ProductAttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->create(\Magento\Catalog\Api\ProductAttributeRepositoryInterface::class);

try {
    $attributeRepository->deleteById('color');
} catch (NoSuchEntityException $e) {
    // No action needed
} catch (\Magento\Framework\Exception\LocalizedException $e) {
    // Attribute is locked (used by pre-existing configurable products in the DB, e.g. Magento sample data).
    // The fixture uses loadByCode() and only creates 'color' if it does not exist, so a locked attribute
    // means the fixture did not create it and must not delete it.
}

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
