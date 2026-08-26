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

namespace MageOS\NetSuiteConnector\Product\Test\Integration\Model\Process\Import;

// @codingStandardsIgnoreStart
use Exception;
use Magento\Catalog\Model\ProductRepository;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig;
use MageOS\NetSuiteConnector\Core\Model\ImportQueueManager;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\ConstructorFactory;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management;
use MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;
use MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker;
use MageOS\NetSuiteConnector\Product\Model\Process\Import\Item;

/**
 * Class ProductImportTest - tests for product import processor
 * @SuppressWarnings(PHPMD)
 */
class ProductImportTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var NetSuiteServiceFaker
     */
    private static $netsuiteServiceFaker;
    /**
     * @var Management|MockObject
     */
    private static $nsHelper;

    /**
     * Path to _files/_files_ns/... folders
     */
    const RELATIVE_PATH_TO_FIXTURES = '../../../';

    public static function setUpBeforeClass(): void
    {
        $fixturesUsed = [
            '_files_ns/attributes_all_types.php',
            '_files_ns/attributes_all_types_rollback.php',
            '_files_ns/inventory_item_to_queue.php',
            '_files_ns/inventory_item_to_queue_rollback.php',
            '_files_ns/inventory_item_configurable_to_queue.php',
            '_files_ns/inventory_item_configurable_to_queue_rollback.php',
            '_files_ns/inventory_item_special_price_to_queue.php',
            '_files_ns/inventory_item_special_price_to_queue_rollback.php',
            '_files_ns/inventory_item_tier_price_to_queue.php',
            '_files_ns/inventory_item_tier_price_to_queue_rollback.php',
            '_files_ns/product_simple_tier_price.php',
            '_files_ns/product_simple_tier_price_rollback.php',
            '_files_ns/product_color.php',
            '_files_ns/product_color_rollback.php',
            '_files_ns/product_url_suffix.php',
            '_files_ns/assembly_item_tier_price_to_queue.php',
            '_files_ns/assembly_item_tier_price_to_queue_rollback.php'
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    /**
     * initial setup for the tests
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->objectManager = $objectManager;
        if (!self::$netsuiteServiceFaker) {
            $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";
            self::$netsuiteServiceFaker = new NetSuiteServiceFaker($path);
        }
        if (!self::$nsHelper) {
            self::$nsHelper = $this->getMockBuilder(Management::class)
                ->onlyMethods(['get'])
                ->setConstructorArgs(
                    [
                        $this->objectManager->create(ModuleRegistry::class),
                        $this->objectManager->create(ConnectorConfig::class),
                        $this->objectManager->create(ConstructorFactory::class)
                    ]
                )
                ->getMock();
        }
        // this is important to run per each test
        $this->objectManager->configure([Management::class => ['shared' => true]]);
        $this->objectManager->addSharedInstance(self::$nsHelper, Management::class);
    }

    /**
     * Scenario:
     * # inventory items exists in NS
     * # no corresponding product in Magento
     * # import it to Queue
     * # process Queue
     * # check that product created
     *
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_products 1
     * @magentoConfigFixture default/mageos_netsuite/products/field_map {"_1569415087847_847":{"netsuite":"itemId","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"sku"},"_1569415217847_123":{"netsuite":"salesDescription","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_varchar"},"_1569415100244_244":{"netsuite":"storeDetailedDescription","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_text"},"_1572438940806_806":{"netsuite":"custitem_test_select","netsuite_settings":"custom_list","netsuite_list_id":"customlist_test_select","netsuite_field_value":"","magento":"test_attribute_select"},"_1572439681997_997":{"netsuite":"custitem_test_checkbox","netsuite_settings":"custom_checkbox","netsuite_list_id":"","netsuite_field_value":"1","magento":"test_attribute_checkbox"},"_1571220127378_367":{"netsuite":"custitem_test_price","netsuite_settings":"custom_simple","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_price"}}
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_netsuite_id 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_visibility 4
     * @magentoConfigFixture default/mageos_netsuite/products/default_status 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_tax_class_id 0
     * @magentoConfigFixture default/mageos_netsuite/products/default_attribute_set_id 4
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_website 0
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_customer_group 32000
     * @magentoConfigFixture default/mageos_netsuite/products/default_website_ids 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_store_ids 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_stored_at_location_level 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_location 1
     * @magentoConfigFixture default/web/seo/use_rewrites 1
     *
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/product_url_suffix.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/attributes_all_types.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/inventory_item_to_queue.php
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testImportNewInventoryItem()
    {
        $inventoryItemProcessor = $this->objectManager->create(
            Item::class
        );

        $parameters = [
            'inventoryItem' => [
                'success' => 1,
                'qty' => 1
            ],
            'customList' => [
                'success' => 1,
                'qty' => 1
            ]
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();

        $importRows = $inventoryItemProcessor->process($this->getMessage()->getObject());
        $rowsToCompare = $importRows->getEntityRows('catalog_product');
        $expectedInventoryRows = $this->getStandardObject('ImportRowsSimpleProduct');
        $this->processProductUpdatedAtField($rowsToCompare);
        $this->assertEquals($expectedInventoryRows, $rowsToCompare);
        $product = $this->commitAndRetrieve($importRows, 'test item');
        $this->assertEquals($product->getData('netsuite_internal_id'), "1111");
        $this->assertEquals($product->getData('url_key'), "test-item");
    }

    /**
     * Scenario:
     * # inventory item exists in NS
     * # inventory item has Special Price (aka price at other Price Level then Base Price)
     * # no corresponding product in Magento
     * # import it to Queue
     * # process Queue
     * # check that product created and has Special Price
     *
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_products 1
     * @magentoConfigFixture default/mageos_netsuite/products/field_map {"_1569415087847_847":{"netsuite":"itemId","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"sku"}}
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_netsuite_id 5
     * @magentoConfigFixture default/mageos_netsuite/products/import_special_price 1
     * @magentoConfigFixture default/mageos_netsuite/products/special_price_price_level 6
     * @magentoConfigFixture default/mageos_netsuite/products/default_visibility 4
     * @magentoConfigFixture default/mageos_netsuite/products/default_status 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_tax_class_id 0
     * @magentoConfigFixture default/mageos_netsuite/products/default_attribute_set_id 4
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_website 0
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_customer_group 32000
     * @magentoConfigFixture default/mageos_netsuite/products/default_website_ids 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_store_ids 1
     * @magentoConfigFixture default/mageos_netsuite/stock/update_stocks_on_product_import 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_stored_at_location_level 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_location 2
     * @magentoConfigFixture default/web/seo/use_rewrites 1
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/inventory_item_special_price_to_queue.php
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testSpecialPriceImportNewInventoryItem()
    {
        $inventoryItemProcessor = $this->objectManager->create(
            Item::class
        );

        $importRows = $inventoryItemProcessor->process($this->getMessage()->getObject());
        $rowsToCompare = $importRows->getEntityRows('catalog_product');
        $expectedInventoryRows = $this->getStandardObject('ImportRowsSpecialPrice');
        $this->processProductUpdatedAtField($rowsToCompare);
        $this->assertEquals($expectedInventoryRows, $rowsToCompare);
        $product = $this->commitAndRetrieve($importRows, 'TestConfSimpleCOLORRED');
        $this->assertEquals($product->getData('netsuite_internal_id'), "89813");
        $this->assertEquals($product->getData('price'), "11233.000000");
        $this->assertEquals($product->getData('special_price'), "9148.050000");
    }

    /**
     * Scenario:
     * # inventory items exists in NS with different qty prices
     * # no corresponding product in Magento
     * # import inventory item to Queue
     * # process Queue
     * # check that corresponding product created
     * # check that qty prices is created as tier prices in Magento for corresponding product
     *
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_products 1
     * @magentoConfigFixture default/mageos_netsuite/products/field_map {"_1569415087847_847":{"netsuite":"itemId","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"sku"},"_1569415217847_123":{"netsuite":"salesDescription","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_varchar"},"_1569415100244_244":{"netsuite":"storeDetailedDescription","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_text"},"_1572438940806_806":{"netsuite":"custitem_test_select","netsuite_settings":"custom_list","netsuite_list_id":"customlist_test_select","netsuite_field_value":"","magento":"test_attribute_select"},"_1572439681997_997":{"netsuite":"custitem_test_checkbox","netsuite_settings":"custom_checkbox","netsuite_list_id":"","netsuite_field_value":"1","magento":"test_attribute_checkbox"},"_1571220127378_367":{"netsuite":"custitem_test_price","netsuite_settings":"custom_simple","netsuite_list_id":"","netsuite_field_value":"","magento":"test_attribute_price"}}
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_netsuite_id 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_visibility 4
     * @magentoConfigFixture default/mageos_netsuite/products/default_status 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_tax_class_id 0
     * @magentoConfigFixture default/mageos_netsuite/products/default_attribute_set_id 4
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_website 0
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_customer_group 32000
     * @magentoConfigFixture default/mageos_netsuite/products/default_website_ids 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_store_ids 1
     * @magentoConfigFixture default/mageos_netsuite/stock/update_stocks_on_product_import 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_stored_at_location_level 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_location 1
     * @magentoConfigFixture default/web/seo/use_rewrites 1
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/product_url_suffix.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/attributes_all_types.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/inventory_item_tier_price_to_queue.php
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testImportNewItemWithTierPrices()
    {
        $inventoryItemProcessor = $this->objectManager->create(
            Item::class
        );
        $importRows = $inventoryItemProcessor->process($this->getMessage()->getObject());
        $rowsToCompare = [];
        $rowsToCompare['catalogProduct'] = $importRows->getEntityRows('catalog_product');
        $rowsToCompare['advanced_pricing'] = $importRows->getEntityRows('advanced_pricing');
        $expectedInventoryRows = $this->getStandardObject('ImportRowsSimpleProductTierPrices');
        $this->processProductUpdatedAtField($rowsToCompare, true);
        $this->assertEqualsCanonicalizing($expectedInventoryRows, $rowsToCompare);
        $product = $this->commitAndRetrieve($importRows, 'test item');
        $this->assertEquals($product->getData('netsuite_internal_id'), "1111");
        $this->assertEquals($product->getData('url_key'), "test-item");
        $tierPrices = $product->getData('tier_price');
        $this->assertNotEmpty($tierPrices);
        $this->assertNotEmpty($tierPrices[0]['cust_group']);
        $this->assertNotEmpty($tierPrices[0]['price_qty']);
        $this->assertEquals($tierPrices[0]['cust_group'], 32000);
        $this->assertEquals($tierPrices[0]['price_qty'], "5.0000");
    }

    /**
     * Scenario:
     * # inventory item exists in NS without qty prices
     * # corresponding product in Magento exists with tier prices
     * # import inventory item to Queue
     * # process Queue
     * # check that corresponding product updated - no tier prices exists
     *
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_products 1
     * @magentoConfigFixture default/mageos_netsuite/products/field_map {"_1569415087847_847":{"netsuite":"itemId","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"sku"}}
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_netsuite_id 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_visibility 4
     * @magentoConfigFixture default/mageos_netsuite/products/default_status 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_tax_class_id 0
     * @magentoConfigFixture default/mageos_netsuite/products/default_attribute_set_id 4
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_website 0
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_customer_group 32000
     * @magentoConfigFixture default/mageos_netsuite/products/default_website_ids 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_store_ids 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_stored_at_location_level 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_location 1
     * @magentoConfigFixture default/web/seo/use_rewrites 1
     * @magentoConfigFixture default/currency/options/base USD
     * @magentoConfigFixture default/currency/options/default USD
     * @magentoConfigFixture default/currency/options/allow USD
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/product_url_suffix.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/attributes_all_types.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/product_simple_tier_price.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/inventory_item_to_queue.php
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testDeleteTierPricesFromProduct()
    {
        $productRepository = $this->objectManager->create(
            ProductRepository::class
        );
        $oldProduct = $productRepository->get('test item');
        $tierPrices = $oldProduct->getData('tier_price');
        $this->assertNotEmpty($tierPrices);

        $inventoryItemProcessor = $this->objectManager->create(
            Item::class
        );
        $importRows = $inventoryItemProcessor->process($this->getMessage()->getObject());
        $updatedProduct = $this->commitAndRetrieve($importRows, 'test item');
        $this->assertEquals($updatedProduct->getData('netsuite_internal_id'), "1111");
        $this->assertEquals($updatedProduct->getData('url_key'), "test-item");
        $tierPrices = $updatedProduct->getData('tier_price');
        $this->assertEmpty($tierPrices);
    }

    /**
     * Scenario:
     * # Create MatrixItem in NS with one child product (Color = Red)
     * # Import MatrixItem to Magento
     * # Check that new Configurable Product with 1 child Simple Product created
     *
     * @magentoConfigFixture default/mageos_netsuite/products/field_map {"_1569415087827_847":{"netsuite":"itemId","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"sku"}, "_1595507272113_113":{"netsuite":"custitem_demos_color","netsuite_settings":"custom_list","netsuite_list_id":"customlist_demos_color_list","netsuite_field_value":"","magento":"color"}}
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_netsuite_id 5
     * @magentoConfigFixture default/mageos_netsuite/products/import_special_price 0
     * @magentoConfigFixture default/mageos_netsuite/products/default_visibility 4
     * @magentoConfigFixture default/mageos_netsuite/products/default_status 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_tax_class_id 0
     * @magentoConfigFixture default/mageos_netsuite/products/default_attribute_set_id 4
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_website 0
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_customer_group 32000
     * @magentoConfigFixture default/mageos_netsuite/products/default_website_ids 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_store_ids 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_stored_at_location_level 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_location 1
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_products 1
     *
     * @magentoConfigFixture default/web/seo/use_rewrites 1
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/product_url_suffix.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Product/Test/Integration/_files_ns/inventory_item_configurable_to_queue.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/product_color.php
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testImportNewConfProduct()
    {
        $inventoryItemProcessor = $this->objectManager->create(
            Item::class
        );
        $parameters = [
            'inventoryItem' => [
                'success' => 1,
                'qty' => 1
            ],
            'customList' => [
                'success' => 1,
                'qty' => 1
            ]
        ];

        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();

        $importRows = $inventoryItemProcessor->process($this->getMessage()->getObject());
        $rowsToCompare = $importRows->getEntityRows('catalog_product');
        $expectedInventoryRows = $this->getStandardObject('ImportRowsConfProduct');
        $this->processProductUpdatedAtField($rowsToCompare);
        $this->assertEqualsCanonicalizing($expectedInventoryRows, $rowsToCompare);
        $product = $this->commitAndRetrieve($importRows, 'testconfsimple');
        $this->assertEquals($product->getData('netsuite_internal_id'), "1111");
        $this->assertEquals($product->getData('type_id'), "configurable");
        $this->assertEquals($product->getData('url_key'), "testconfsimple");
        $children = $product->getTypeInstance()->getUsedproducts($product);
        $this->assertNotEmpty($children);
        $this->assertEquals(1, count($children));
        $this->assertEquals($children[0]->getData('sku'), 'TestConfSimpleCOLORRED');
        $this->assertEquals($children[0]->getData('netsuite_internal_id'), '89813');
    }

    /**
     * Scenario
     * # we have process of importing started and failed some time before on 2 page
     * # total mount of pages with products to be imported to queue - 4
     * # we have error during fetching records from NetSuite on 3 run
     * # we have file with fetching information in the system so next import starts from the place it was stopped
     * # we start process without errors
     * # check that mutex file was updated
     *
     * @magentoConfigFixture default/mageos_netsuite/products/field_map {"_1569415087827_847":{"netsuite":"itemId","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"sku"}, "_1595507272113_113":{"netsuite":"custitem_demos_color","netsuite_settings":"custom_list","netsuite_list_id":"customlist_demos_color_list","netsuite_field_value":"","magento":"color"}}
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_netsuite_id 5
     * @magentoConfigFixture default/mageos_netsuite/products/import_special_price 0
     * @magentoConfigFixture default/mageos_netsuite/products/default_visibility 4
     * @magentoConfigFixture default/mageos_netsuite/products/default_status 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_tax_class_id 0
     * @magentoConfigFixture default/mageos_netsuite/products/default_attribute_set_id 4
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_website 0
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_customer_group 32000
     * @magentoConfigFixture default/mageos_netsuite/products/default_website_ids 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_store_ids 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_stored_at_location_level 1
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_location 1
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_products 1
     * @magentoConfigFixture default/web/seo/use_rewrites 1
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testQueryNetSuiteWithMutex()
    {
        $inventoryItemProcessor = $this->objectManager->create(
            Item::class
        );
        $parameters = [
            'failedAfter' => '2',
            'totalPages' => '4',
            'pageNumber' => '2',
            'searchId' => '2231'
        ];

        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();
        // add mutex with data
        $inventoryItemProcessor->saveCurrentSearchIdAndPage(
            $parameters['searchId'],
            $parameters['pageNumber'],
            $parameters['totalPages']
        );
        // query NSC
        $result = $inventoryItemProcessor->queryNetsuite('2021-21-03 00:00:00', true);
        $this->assertTrue($result instanceof \NetSuite\Classes\Record);
        // check file with mutex
        $mutexData = $inventoryItemProcessor->getCurrentSearchIdAndPage();
        $this->assertEquals(2231, $mutexData['searchId']);
        $this->assertEquals(3, $mutexData['pageNumber']);
        $this->assertEquals(4, $mutexData['totalPages']);
    }

    /**
     * @param $importRows
     * @param $sku
     * @return
     */
    private function commitAndRetrieve($importRows, $sku)
    {
        $importQueueManager = $this->objectManager->create(
            ImportQueueManager::class
        );
        $importQueueManager->import($importRows);
        $importQueueManager->commit();

        $productRepository = $this->objectManager->create(
            ProductRepository::class
        );
        return $productRepository->get($sku);
    }

    /**
     * Create Message which we process
     *
     * @return Message
     * @throws Exception
     */
    private function getMessage(): MessageInterface
    {
        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(\MageOS\NetSuiteConnector\Core\Enum\Message\Queue::IMPORT(), 50);

        $this->assertCount(1, $messages);
        foreach ($messages as $originalMessage) {
            $message = $originalMessage;
        }

        return $message;
    }

    /**
     * Retrieve proper example(standard) of the ImportRowList object to compare
     *
     * @return object
     */
    private function getStandardObject($fileName): array
    {
        $file = __DIR__ . "/../../../_files/standard/" . $fileName;
        return json_decode(file_get_contents($file), true);
    }

    /*
    * Helper methods for tests
    */
    private function setNetSuiteServiceFaker()
    {
        self::$nsHelper->method('get')
            ->willReturn(self::$netsuiteServiceFaker);
    }

    /**
     * Helper method for tests
     */
    private function processProductUpdatedAtField(array &$rowsToCompare, bool $isWrapper = false): array
    {
        if ($rowsToCompare) {
            $updatedAt = $rowsToCompare[0]['updated_at'] ?? null;
            if ($isWrapper) {
                $updatedAt = $rowsToCompare['catalogProduct'][0]['updated_at'] ?? null;
            }

            if ($updatedAt && $this->validateProductUpdatedAtField($updatedAt)) {
                if ($isWrapper) {
                    unset($rowsToCompare['catalogProduct'][0]['updated_at']);
                } else {
                    unset($rowsToCompare[0]['updated_at']);
                }
            }
        }

        return $rowsToCompare;
    }

    /**
     * Helper method for tests
     *
     * Validation: is updated_at date is equal today date
     */
    private function validateProductUpdatedAtField(string $date): bool
    {
        $currentDate = new \DateTime(date('Y-m-d', strtotime($date)));
        $today = (new \DateTime('now'));
        $difference = $currentDate->diff($today)->days;
        if ($difference === 0) {
            return true;
        }

        return false;
    }

    /**
     * Scenario:
     * # Assembly item exists in NS
     * # Assembly item has Tier Price (qty only, no group prices)
     * # no corresponding product in Magento
     * # import it to Queue
     * # process Queue
     * # check that product created and has Tier Price
     *
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_products 1
     * @magentoConfigFixture default/mageos_netsuite/products/field_map {"_1569415087847_847":{"netsuite":"itemId","netsuite_settings":"standard_field","netsuite_list_id":"","netsuite_field_value":"","magento":"sku"}}
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_netsuite_id 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_visibility 4
     * @magentoConfigFixture default/mageos_netsuite/products/default_status 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_tax_class_id 0
     * @magentoConfigFixture default/mageos_netsuite/products/default_attribute_set_id 4
     * @magentoConfigFixture default/mageos_netsuite/products/tier_price_customer_group 32000
     * @magentoConfigFixture default/mageos_netsuite/products/price_level_map "[]" 
     * @magentoConfigFixture default/mageos_netsuite/products/default_website_ids 1
     * @magentoConfigFixture default/mageos_netsuite/products/default_store_ids 1
     * @magentoConfigFixture default/currency/options/base USD
     * @magentoConfigFixture default/currency/options/default USD
     * @magentoConfigFixture default/currency/options/allow USD
     * @magentoConfigFixture default/web/seo/use_rewrites 1
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files_ns/assembly_item_tier_price_to_queue.php
     *
     * @magentoAppIsolation enabled
     * @magentoDbIsolation enabled
     */
    public function testImportAssemblyTierPrice()
    {
        $inventoryItemProcessor = $this->objectManager->create(
            Item::class
        );

        $importRows = $inventoryItemProcessor->process($this->getMessage()->getObject());
        $rowsToCompare = $importRows->getEntityRows('catalog_product');
        $expectedInventoryRows = $this->getStandardObject('ImportRowsTierPrice');
        $this->processProductUpdatedAtField($rowsToCompare);
        $this->assertEquals($expectedInventoryRows, $rowsToCompare);
        $product = $this->commitAndRetrieve($importRows, 'TestTier');
        $this->assertEquals($product->getData('netsuite_internal_id'), "5555");
        $this->assertEquals($product->getData('price'), "11233.000000");
        $tierPrices = $product->getData('tier_price');
        $this->assertNotEmpty($tierPrices);
        $this->assertNotEmpty($tierPrices[0]['cust_group']);
        $this->assertNotEmpty($tierPrices[0]['price_qty']);
        $this->assertEquals($tierPrices[0]['cust_group'], 32000);
        $this->assertEquals($tierPrices[0]['price_qty'], "5.0000");
    }
}
