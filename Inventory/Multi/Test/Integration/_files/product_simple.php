<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();

/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/** @var \Magento\Catalog\Api\CategoryLinkManagementInterface $categoryLinkManagement */
$categoryLinkManagement = $objectManager->create(\Magento\Catalog\Api\CategoryLinkManagementInterface::class);

/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->isObjectNew(true);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(1)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product')
    ->setSku('simple')
    ->setPrice(10)
    ->setWeight(1)
    ->setShortDescription("Short description")
    ->setTaxClassId(0)
    ->setDescription('Description with <b>html tag</b>')
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(
        [
            'use_config_manage_stock'   => 1,
            'qty'                       => 10,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ]
    )
    ->setCanSaveCustomOptions(true)
    ->setHasOptions(false);

$product->setCustomAttribute('netsuite_internal_id', '1');

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryFactory */
$productRepositoryFactory = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$productRepositoryFactory->save($product);

$categoryLinkManagement->assignProductToCategories(
    $product->getSku(),
    [2]
);

$sourceItemFactory = $objectManager->create(\Magento\InventoryApi\Api\Data\SourceItemInterfaceFactory::class);
$source1 = $sourceItemFactory->create();
$source1->setSku('simple');
$source1->setSourceCode('source_1');
$source1->setQuantity(10);
$source1->setStatus(1);
$source2 = $sourceItemFactory->create();
$source2->setSku('simple');
$source2->setSourceCode('source_2');
$source2->setQuantity(0);
$source2->setStatus(0);
$sourceItems = [$source1, $source2];
$saveSourceItems = $objectManager->create(\Magento\Inventory\Model\SourceItem\Command\SourceItemsSave::class);
$saveSourceItems->execute($sourceItems);
