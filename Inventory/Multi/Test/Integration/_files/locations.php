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
\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();
/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
$sourceRepository = $objectManager->create(\Magento\InventoryApi\Api\SourceRepositoryInterface::class);
$sourceFactory = $objectManager->create(\Magento\InventoryApi\Api\Data\SourceInterfaceFactory::class);

for ($i = 1; $i <= 2; $i++) {
    $source = $sourceFactory->create();
    $source->setName('Source_' . $i);
    $source->setSourceCode('source_' . $i);
    $source->setEnabled(true);
    $source->setData('netsuite_internal_id', $i);
    $source->setPostcode('12345');
    $source->setCountryId('US');
    $sourceRepository->save($source);
}
