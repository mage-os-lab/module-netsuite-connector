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
/** @var \Magento\TestFramework\ObjectManager $objectManager */
$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

\Magento\TestFramework\Helper\Bootstrap::getInstance()->reinitialize();

/** @var $attribute \Magento\Catalog\Model\ResourceModel\Eav\Attribute */
$attribute = $objectManager->create(\Magento\Catalog\Model\ResourceModel\Eav\Attribute::class);
$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);

/** @var $installer \Magento\Catalog\Setup\CategorySetup */
$installer = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->create(
    \Magento\Catalog\Setup\CategorySetup::class
);

/** @var \Magento\Eav\Api\AttributeRepositoryInterface $attributeRepository */
$attributeRepository = $objectManager->create(\Magento\Eav\Api\AttributeRepositoryInterface::class);
/** @var \Magento\Eav\Api\AttributeOptionManagementInterface $optionManagement */
$optionManagement = $objectManager->create(\Magento\Eav\Api\AttributeOptionManagementInterface::class);
/** @var \Magento\Eav\Api\Data\AttributeOptionInterfaceFactory $optionFactory */
$optionFactory = $objectManager->create(\Magento\Eav\Api\Data\AttributeOptionInterfaceFactory::class);

if (!$attribute->loadByCode(4, 'color')->getId()) {
    $attribute->setData(
        [
            'attribute_code'                => 'color',
            'entity_type_id'                => $installer->getEntityTypeId('catalog_product'),
            'is_user_defined'               => 1,
            'frontend_input'                => 'select',
            'is_unique'                     => 0,
            'is_required'                   => 0,
            'is_searchable'                 => 0,
            'is_visible_in_advanced_search' => 0,
            'is_comparable'                 => 0,
            'is_filterable'                 => 0,
            'is_filterable_in_search'       => 0,
            'is_used_for_promo_rules'       => 0,
            'is_html_allowed_on_front'      => 1,
            'is_visible_on_front'           => 1,
            'used_in_product_listing'       => 1,
            'used_for_sort_by'              => 0,
            'is_global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
            'frontend_label'                => 'Color',
            'backend_type'                  => 'int',
        ]
    );
    $attribute->save();
}

if ($attribute->getId()) {
    /* Assign attribute to attribute set */
    $installer->addAttributeToGroup('catalog_product', 'Default', 'General', $attribute->getId());
}

$eavConfig->clear();

/** @var \Magento\Catalog\Api\ProductRepositoryInterface $productRepositoryFactory */
$productRepositoryFactory = $objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class);
$colorAttribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'color');

$existingOptions = [];
foreach ($optionManagement->getItems(\Magento\Catalog\Model\Product::ENTITY, 'color') as $existingOption) {
    $label = (string)$existingOption->getLabel();
    if ($label !== '') {
        $existingOptions[strtolower($label)] = $existingOption->getValue();
    }
}

foreach (['Red', 'Blue'] as $colorLabel) {
    if (isset($existingOptions[strtolower($colorLabel)])) {
        continue;
    }
    /** @var \Magento\Eav\Api\Data\AttributeOptionInterface $newOption */
    $newOption = $optionFactory->create();
    $newOption->setLabel($colorLabel);
    $newOption->setSortOrder(0);
    $newOption->setIsDefault(false);
    $optionManagement->add(\Magento\Catalog\Model\Product::ENTITY, 'color', $newOption);
}

$eavConfig->clear();
$colorAttribute = $eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'color');
$colorOptions = [];
foreach ($colorAttribute->getSource()->getAllOptions(false) as $option) {
    if (isset($option['label'], $option['value'])) {
        $colorOptions[strtolower((string)$option['label'])] = $option['value'];
    }
}
$redOptionId = $colorOptions['red'] ?? null;
$blueOptionId = $colorOptions['blue'] ?? null;
if (!$redOptionId || !$blueOptionId) {
    throw new \RuntimeException(
        'Color attribute options could not be resolved after add: '
        . json_encode($colorOptions)
    );
}
// Create first simple product
/** @var $product \Magento\Catalog\Model\Product */
$product = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product->isObjectNew(true);
$product->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(11)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product')
    ->setSku('simple1')
    ->setPrice(10)
    ->setWeight(1)
    ->setShortDescription("Short description")
    ->setTaxClassId(0)
    ->setDescription('Description')
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setUrlKey('simple-11')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(
        [
            'use_config_manage_stock'   => 0,
            'manage_stock'              => 1,
            'qty'                       => 10,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ]
    )->setHasOptions(false)
    ->setCustomAttribute('netsuite_internal_id', '1')
    ->setCustomAttribute('color', $redOptionId)
    ->setData('color', $redOptionId);
$productRepositoryFactory->save($product);

// Create second simple product
$product2 = $objectManager->create(\Magento\Catalog\Model\Product::class);
$product2->isObjectNew(true);
$product2->setTypeId(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE)
    ->setId(12)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Simple Product')
    ->setSku('simple2')
    ->setPrice(10)
    ->setWeight(1)
    ->setShortDescription("Short description")
    ->setTaxClassId(0)
    ->setDescription('Description')
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setUrlKey('simple-12')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(
        [
            'use_config_manage_stock'   => 0,
            'manage_stock'              => 1,
            'qty'                       => 5,
            'is_qty_decimal'            => 0,
            'is_in_stock'               => 1,
        ]
    )->setHasOptions(false)
    ->setCustomAttribute('netsuite_internal_id', '2')
    ->setCustomAttribute('color', $blueOptionId)
    ->setData('color', $blueOptionId);
$productRepositoryFactory->save($product2);

// Create configurable product
$productConf = $objectManager->create(\Magento\Catalog\Model\Product::class);
$productConf->isObjectNew(true);
$productConf->setTypeId(\Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE)
    ->setId(13)
    ->setAttributeSetId(4)
    ->setWebsiteIds([1])
    ->setName('Configurable Product')
    ->setSku('conf')
    ->setShortDescription("Short description")
    ->setTaxClassId(0)
    ->setDescription('Description')
    ->setMetaTitle('meta title')
    ->setMetaKeyword('meta keyword')
    ->setMetaDescription('meta description')
    ->setUrlKey('conf-1-2')
    ->setVisibility(\Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
    ->setStatus(\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
    ->setStockData(
        [
            'use_config_manage_stock'   => 0,
            'manage_stock'              => 1,
            'is_in_stock'               => 1,
        ]
    )->setHasOptions(false)
    ->setCustomAttribute('netsuite_internal_id', '3');
$productRepositoryFactory->save($productConf);

// Assign simple products to configurable
$optionRepo = $objectManager->create(\Magento\ConfigurableProduct\Api\OptionRepositoryInterface::class);
$option = $objectManager->create(\Magento\ConfigurableProduct\Model\Product\Type\Configurable\AttributeFactory::class)
    ->create();
$optionValue =
    $objectManager->create(\Magento\ConfigurableProduct\Model\Product\Type\Configurable\OptionValueFactory::class)
    ->create();
$optionValue->setValueIndex(9999);
$option->setAttributeId($colorAttribute->getAttributeId())
    ->setLabel('Color')
    ->setPosition(0)
    ->setIsUseDefault(1)
    ->setValues([$optionValue]);
$optionRepo->save('conf', $option);

$linkManagement = $objectManager->create(\Magento\ConfigurableProduct\Api\LinkManagementInterface::class);
$linkManagement->addChild('conf', 'simple1');
$linkManagement->addChild('conf', 'simple2');
