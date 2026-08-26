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
// @codingStandardsIgnoreStart
/**
 * fixture creates 5 eav attributes for Product entity:
 * test_attribute_varchar - varchar attribute
 * test_attribute_text - text attribute with textarea
 * test_attribute_select - select attribute without options
 * test_attribute_checkbox - true/false attribute
 * test_attribute_price - attribute with price model
 *
 * this attributes used for product attribute mapping
 *
 * phpcs:ignoreFile
 */

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Entity\Type;
use Magento\TestFramework\Helper\Bootstrap;

$objectManager = Bootstrap::getObjectManager();

$entityType = $objectManager->create(Type::class)->loadByCode(Product::ENTITY);

/** @var $installer \Magento\Catalog\Setup\CategorySetup */
$installer = $objectManager->create(\Magento\Catalog\Setup\CategorySetup::class);

/** @var \Magento\Eav\Model\Config $eavConfig */
$eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);

$fullReindexNeeded = false;

if (!$installer->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_attribute_varchar')) {
    $installer->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
        'test_attribute_varchar',
        [
            'type' => 'varchar',
            'label' => 'Test Attribute Varchar',
            'input' => 'text',
            'global' => Attribute::SCOPE_GLOBAL,
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
}

if (!$installer->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_attribute_text')) {
    $installer->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
        'test_attribute_text',
        [
            'type' => 'text',
            'label' => 'Test Attribute Text',
            'input' => 'textarea',
            'global' => Attribute::SCOPE_STORE,
            'user_defined' => true,
            'used_in_product_listing' => false,
            'required' => false,
            'visible' => true,
            'default' => '0',
            'searchable' => false,
            'filterable' => '0',
            'comparable' => false,
            'visible_on_front' => true,
            'is_used_for_promo_rules' => '1',
            'is_html_allowed_on_front' => '1',
            'is_filterable_in_grid' => true,
            'unique' => false,
            'apply_to' => 'simple,configurable,bundle,grouped'
        ]
    );
}

if (!$installer->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_attribute_select')) {
    $installer->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
        'test_attribute_select',
        [
            'type' => 'int',
            'backend' => '',
            'frontend' => '',
            'label' => 'Test Attribute Select',
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
}
if (!$installer->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_attribute_checkbox')) {
    $installer->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
        'test_attribute_checkbox',
        [
            'type' => 'int',
            'backend' => '',
            'frontend' => '',
            'label' => 'Test Attribute Checkbox',
            'input' => 'boolean',
            'source' => \Magento\Eav\Model\Entity\Attribute\Source\Boolean::class,
            'global' => Attribute::SCOPE_GLOBAL,
            'user_defined' => true,
            'used_in_product_listing' => false,
            'required' => false,
            'visible' => true,
            'default' => '0',
            'searchable' => false,
            'filterable' => '2',
            'comparable' => false,
            'visible_on_front' => true,
            'is_used_for_promo_rules' => '1',
            'is_html_allowed_on_front' => '1',
            'is_filterable_in_grid' => true,
            'unique' => false,
            'apply_to' => 'simple,configurable,bundle,grouped'
        ]
    );
}

if (!$installer->getAttribute(\Magento\Catalog\Model\Product::ENTITY, 'test_attribute_price')) {
    $installer->addAttribute(
        \Magento\Catalog\Model\Product::ENTITY,
        'test_attribute_price',
        [
            'type' => 'decimal',
            'frontend' => '',
            'label' => 'Test Attribute Price',
            'input' => 'price',
            'backend' => \Magento\Catalog\Model\Product\Attribute\Backend\Price::class,
            'global' => Attribute::SCOPE_GLOBAL,
            'user_defined' => true,
            'used_in_product_listing' => false,
            'required' => false,
            'visible' => true,
            'default' => '0',
            'searchable' => false,
            'filterable' => '2',
            'comparable' => false,
            'visible_on_front' => true,
            'is_used_for_promo_rules' => '1',
            'is_html_allowed_on_front' => '1',
            'is_filterable_in_grid' => true,
            'unique' => false,
            'apply_to' => 'simple,configurable,bundle,grouped'
        ]
    );
}
$eavConfig->clear();
