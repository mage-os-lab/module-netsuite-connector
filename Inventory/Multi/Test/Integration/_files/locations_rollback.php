<?php declare(strict_types=1);
/**
 *  RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */
use Magento\Framework\Exception\NoSuchEntityException;

\Magento\TestFramework\Helper\Bootstrap::getInstance()->getInstance()->reinitialize();

/** @var \Magento\Framework\Registry $registry */
$registry = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get('Magento\Framework\Registry');

$registry->unregister('isSecureArea');
$registry->register('isSecureArea', true);

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepository */
$sourceRepository = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()
    ->get(\Magento\InventoryApi\Api\SourceRepositoryInterface::class);
$sourceCodes = ['source_1', 'source_2'];
try {
    foreach ($sourceCodes as $sourceCode) {
        $source = $sourceRepository->get($sourceCode);
        $sourceRepository->delete($source);
    }
} catch (NoSuchEntityException $e) {
    //do nothing
}
$registry->unregister('isSecureArea');
$registry->register('isSecureArea', false);
