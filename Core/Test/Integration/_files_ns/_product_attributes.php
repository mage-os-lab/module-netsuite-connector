<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Indexer\Price\DefaultPrice;
use Magento\Eav\Model\Entity\Type;
use Magento\Indexer\Model\Indexer;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$entityType = $objectManager->create(Type::class)->loadByCode(Product::ENTITY);

/** @var $installer \Magento\Catalog\Setup\CategorySetup */
$installer = $objectManager->create(\Magento\Catalog\Setup\CategorySetup::class);

/** @var \Magento\Eav\Model\Config $eavConfig */
$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);

$fullReindexNeeded = false;

if (!$installer->getAttribute(Product::ENTITY, 'product_color')) {
    $installer->removeAttribute(Product::ENTITY, 'product_color');
    $installer->addAttribute(
        Product::ENTITY,
        'product_color',
        [
            'type' => 'int',
            'backend' => '',
            'frontend' => '',
            'label' => 'Product Color',
            'input' => 'select',
            'class' => '',
            'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Table',
            'global' => Attribute::SCOPE_GLOBAL,
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
        'catalog_product_price', 'catalog_product_flat', 'catalog_category_flat',
        'catalog_category_product', 'catalog_product_price', 'catalog_product_attribute',
        'cataloginventory_stock', 'catalogrule_rule', 'catalogrule_product', 'catalogsearch_fulltext',
        'targetrule_product_rule', 'targetrule_rule_product', 'salesrule_rule'
    ];

    foreach ($indexerNames as $indexId) {
        try {
            $indexer = Bootstrap::getObjectManager()->create(Indexer::class);
            $indexer->load($indexId);
            $indexer->reindexAll();
        } catch (\Exception $e) {
            echo $e->getTraceAsString();// phpcs:ignore
        }
    }

    // set to false, otherwise individual row indexing doesn't work
    Bootstrap::getObjectManager()
        ->get(DefaultPrice::class)
        ->getTableStrategy()
        ->setUseIdxTable(false);

}
