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
 */

//phpcs:ignoreFile

namespace MageOS\NetSuiteConnector\Product\Test\Integration\Model\Config;

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig;

/**
 * Class ProductConfigTest - tests ProductConfig behavior (settings provider)
 * @SuppressWarnings(PHPMD)
 */
class ProductConfigTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductPrefetchIdSource $prefetchCommand
     */
    private $productConfig;

    /**
     *
     */
    protected function setUp():void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->productConfig = $objectManager->create(ProductConfig::class);
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/products/push_line_items_with_bundles 1
     * @magentoConfigFixture default/mageos_netsuite/products/import_special_price 1
     * @magentoConfigFixture default/mageos_netsuite/products/special_price_price_level 6
     * @magentoConfigFixture default/mageos_netsuite/products/default_store_ids 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_attribute_set_id 4
     * @magentoConfigFixture default/mageos_netsuite/products/default_visibility 4
     * @magentoConfigFixture default/mageos_netsuite/products/default_status 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_website_ids 1
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_netsuite_id 1
     * @magentoConfigFixture default/mageos_netsuite/products/field_map {"_1569415087847_847":{"netsuite":"itemId","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"sku"},"_1569415217847_123":{"netsuite":"salesDescription","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_varchar"},"_1569415100244_244":{"netsuite":"storeDetailedDescription","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_text"},"_1572438940806_806":{"netsuite":"custitem_test_select","netsuite_settings":"custom_list","netsuite_list_id":"customlist_test_select","netsuite_field_value":"","magento":"test_attribute_select"},"_1572439681997_997":{"netsuite":"custitem_test_checkbox","netsuite_settings":"custom_checkbox","netsuite_list_id":"","netsuite_field_value":"1","magento":"test_attribute_checkbox"},"_1571220127378_367":{"netsuite":"custitem_test_price","netsuite_settings":"custom_simple","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_price"}}
     * @magentoConfigFixture default/mageos_netsuite/products/default_tax_class_id 0
     * @magentoConfigFixture default/mageos_netsuite/products/related_products_field related_products
     * @magentoConfigFixture default/mageos_netsuite/products/upsells_field upsell_field
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_customer_group 32000
     * @magentoConfigFixture default/mageos_netsuite/products/html_tags {"tags":{"tag":"p"}}
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_map {"price_map":{"1":"3"}}
     * @magentoDbIsolation enabled
     */
    public function testConfigReceiving()
    {
        $expectedResult = [
            'push_line_items_with_bundles' => true,
            'default_store_ids' => '1',
            'default_attribute_set_id' => '4',
            'default_visibility' => '4',
            'default_status' => '1',
            'default_website_ids' => '1',
            'price_level_netsuite_id' => '1',
            'import_special_price' => 1,
            'special_price_price_level' => 6,
            'field_map' => json_decode(
                '{"_1569415087847_847":{"netsuite":"itemId","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"sku"},"_1569415217847_123":{"netsuite":"salesDescription","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_varchar"},"_1569415100244_244":{"netsuite":"storeDetailedDescription","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_text"},"_1572438940806_806":{"netsuite":"custitem_test_select","netsuite_settings":"custom_list","netsuite_list_id":"customlist_test_select","netsuite_field_value":"","magento":"test_attribute_select"},"_1572439681997_997":{"netsuite":"custitem_test_checkbox","netsuite_settings":"custom_checkbox","netsuite_list_id":"","netsuite_field_value":"1","magento":"test_attribute_checkbox"},"_1571220127378_367":{"netsuite":"custitem_test_price","netsuite_settings":"custom_simple","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_price"}}',
                true
            ),
            'default_tax_class_id' => 0,
            'related_products_field' => 'related_products',
            'upsells_field' => 'upsell_field',
            'tier_price_customer_group' => '32000',
            'html_tags' => json_decode('{"tags":{"tag":"p"}}', true),
            'price_level_map' => json_decode('{"price_map":{"1":"3"}}', true),
        ];
        $actualResult = [
            'push_line_items_with_bundles' => $this->productConfig->getPushLineItemsWithBundles(),
            'default_store_ids' => $this->productConfig->getDefaultStoreIds(),
            'default_attribute_set_id' => $this->productConfig->getDefaultAttributeSetId(),
            'default_visibility' => $this->productConfig->getDefaultVisibility(),
            'default_status' => $this->productConfig->getDefaultStatus(),
            'default_website_ids' => $this->productConfig->getDefaultWebsiteIds(),
            'price_level_netsuite_id' => $this->productConfig->getPriceLevelNetsuiteId(),
            'import_special_price' => $this->productConfig->getImportSpecialPrice(),
            'special_price_price_level' => $this->productConfig->getSpecialPricePriceLevel(),
            'field_map' => $this->productConfig->getFieldMap(),
            'default_tax_class_id' => $this->productConfig->getDefaultTaxClassId(),
            'related_products_field' => $this->productConfig->getRelatedProductsField(),
            'upsells_field' => $this->productConfig->getUpsellsField(),
            'tier_price_customer_group' => $this->productConfig->getTierPriceCustomerGroup(),
            'html_tags' => $this->productConfig->getHtmlTags(),
            'price_level_map' => $this->productConfig->getPriceLevelMap(),
        ];
        $this->assertEquals($expectedResult, $actualResult);
    }
}
