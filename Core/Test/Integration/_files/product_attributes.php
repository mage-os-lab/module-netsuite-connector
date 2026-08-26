<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$entityType = $objectManager->create('Magento\Eav\Model\Entity\Type')
    ->loadByCode(\Magento\Catalog\Model\Product::ENTITY);

/** @var $installer \Magento\Catalog\Setup\CategorySetup */
$installer = Bootstrap::getObjectManager()->create('Magento\Catalog\Setup\CategorySetup');

/** @var \Magento\Eav\Model\Config $eavConfig */
$eavConfig = \Magento\TestFramework\Helper\Bootstrap::getObjectManager()->get('Magento\Eav\Model\Config');

$fullReindexNeeded = false;

if (!$installer->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'frame_measurement')) {
    //$installer->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'frame_measurement');
    $installer->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
        'frame_measurement',
        [
            'type' => 'varchar',
            'label' => 'Frame Measurement',
            'input' => 'text',
            'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => false,
            'default' => '0',
            'searchable' => false,
            'filterable' => '0',
            'comparable' => false,
            'visible_on_front' => true,
            'used_in_product_listing' => true,
            'is_used_for_promo_rules' => '1',
            'is_html_allowed_on_front' => '1',
            'is_filterable_in_grid' => true,
            'unique' => false,
            'apply_to' => 'simple,configurable,bundle,grouped'
        ]
    );

    $fullReindexNeeded = true;
}

if (!$installer->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_super_attr')) {
    //$installer->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_super_attr');
    $installer->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
        'test_super_attr',
        [
            'type' => 'int',
            'backend' => '',
            'frontend' => '',
            'label' => 'Test Attribute',
            'input' => 'select',
            'class' => '',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
            'global' => \Magento\Catalog\Model\ResourceModel\Eav\Attribute::SCOPE_GLOBAL,
            'visible' => true,
            'required' => false,
            'user_defined' => false,
            'default' => '0',
            'searchable' => false,
            'filterable' => '2',
            'comparable' => false,
            'visible_on_front' => true,
            'used_in_product_listing' => true,
            'is_used_for_promo_rules' => '1',
            'is_html_allowed_on_front' => '1',
            'is_filterable_in_grid' => true,
            'unique' => false,
            'apply_to' => 'simple,configurable,bundle,grouped'
        ]
    );

    $fullReindexNeeded = true;

}

$eavConfig->clear();

if ($fullReindexNeeded) {
    // this is just to ensure that index tables structures is correct
    $indexerNames = [
        'catalog_product_price',
        'catalog_product_flat',
        'catalog_category_flat',
        'catalog_category_product',
        'catalog_product_price',
        'catalog_product_attribute',
        'cataloginventory_stock',
        'catalogrule_rule',
        'catalogrule_product',
        'catalogsearch_fulltext',
        'targetrule_product_rule',
        'targetrule_rule_product',
        'salesrule_rule'
    ];
        

    foreach ($indexerNames as $indexId) {
        $indexer = Bootstrap::getObjectManager()->create('Magento\Indexer\Model\Indexer');
        $indexer->load($indexId);
        try {
            $indexer->reindexAll();
        } catch (\Exception $e) {
            echo $e->getMessage();// phpcs:ignore
        }
    }

    // set to false, otherwise individual row indexing doesn't work
    Bootstrap::getObjectManager()
        ->get(\Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\DefaultPrice::class)
        ->getTableStrategy()
        ->setUseIdxTable(false);

}
