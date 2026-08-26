<?php
//phpcs:disable
namespace MageOS\NetSuiteConnector\Inventory\Single\Test\Integration\Model\Process\Import;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management as ServiceManagement;
use MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;

/**
 * Class ProcessTest integration tests of stock update feature.
 *
 * @SuppressWarnings(PHPMD)
 */
class StockTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker
     */
    private static $netsuiteServiceFaker;
    /**
     * @var ServiceManagement|MockObject
     */
    private static $nsHelper;
    /**
     * @var Repository|MockObject
     */
    private static $nsRepositoryHelper;

    /**
     * Path to _files/_files_ns/... folders
     */
    const RELATIVE_PATH_TO_FIXTURES = '../../../';

    public static function setUpBeforeClass():void
    {
        $fixturesUsed = [
            '_files_ns_response/GetServerTimeRequest',
            '_files_ns_response/ItemSearchAdvanced',
            '_files_ns_response/ItemSearchAdvancedConf1',
            '_files_ns_response/ItemSearchAdvancedConf2',
            '_files/product_simple.php',
            '_files/product_simple_rollback.php',
            '_files/products_conf.php',
            '_files/products_conf_rollback.php'
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    /**
     * $netusiteServicerFaker is a replacement class for WSDL Netsuite class
     * $nsHelper is a mock because we use getNetSuiteService() call to get access to WSDL Netsuite class
     *
     * Because how Magento & phpunit works, we need to have them as static values. Main reason - the Mock is
     * cached but on the second test we create a new instance of the Mock but the old one is actually still active
     * in Magento code.
     */
    protected function setUp():void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->objectManager = $objectManager;

        if (!self::$netsuiteServiceFaker) {
            $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";
            self::$netsuiteServiceFaker = new \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker($path);
            /** @var \MageOS\NetSuiteConnector\Core\Helper\Data $netsuiteHelper */
        }
        if (!self::$nsHelper) {
            self::$nsHelper = $this->getMockBuilder(ServiceManagement::class)
                ->onlyMethods(['get'])
                ->setConstructorArgs(
                    [
                        $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry::class),
                        $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig::class),
                        $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\ConstructorFactory::class),
                    ]
                )
                ->getMock();
        }
        self::$nsHelper->method('get')
            ->willReturn(self::$netsuiteServiceFaker);

        // this is important to run per each test
        $this->objectManager->configure([ServiceManagement::class => ['shared' => true]]);
        $this->objectManager->addSharedInstance(self::$nsHelper, ServiceManagement::class);
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_stock_updates 1
     * @magentoConfigFixture default/mageos_netsuite/stock/update_stocks_every_n_minutes 0
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_stored_at_location_level 1
     * @magentoConfigFixture default/mageos_netsuite/stock/custom_search_id 100
     * @magentoConfigFixture default/mageos_netsuite/stock/qty_field_name quantityOnHand
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/product_simple.php
     * @magentoDbIsolation enabled
     */
    public function testProcessStockImport()
    {
        $productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $stockRegistry = $this->objectManager->get(\Magento\CatalogInventory\Api\StockRegistryInterface::class);
        $product = $productRepository->get('simple');
        $stock = $stockRegistry->getStockItem($product->getId());

        /**
         * Validate DataFixture before checking anything further
         */
        $this->assertEquals(100, $stock->getQty());

        /**
         * Set NS response values
         */
        $parameters = [
            'search_success' => 1
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $nsRepositoryHelper = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['mapNetSuiteIdsToProductIds'])
            ->setConstructorArgs(
                [
                    $this->objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilderFactory::class),
                    $this->objectManager->create(\Magento\Framework\App\ResourceConnection::class),
                    $this->objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class),
                    $this->objectManager->create(\Magento\Eav\Model\Entity\Attribute::class),
                    $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Helper\EavHelper::class),
                ]
            )
            ->getMock();
        $nsRepositoryHelper->method('mapNetSuiteIdsToProductIds')
            ->willReturn(['1' => ['entity_id' => '1', 'type_id' => '4', 'sku' => 'simple']]);
        $this->objectManager->configure([Repository::class => ['shared' => true]]);
        $this->objectManager->addSharedInstance($nsRepositoryHelper, Repository::class);

        /**
         * Run the code
         */
        /** @var \MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock $process */
        $process = $this->objectManager->get(\MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock::class);
        $process->process();

        /**
         * Clean up stock storage to force fresh data as stockItem is cached
         */
        $stockRegistryStorage = $this->objectManager->get(\Magento\CatalogInventory\Model\StockRegistryStorage::class);
        $stockRegistryStorage->clean();

        /**
         * Confirm the quantity changed, get the stock item again
         */
        $stock = $stockRegistry->getStockItem($product->getId());
        $this->assertEquals(200, $stock->getQty());
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_stock_updates 1
     * @magentoConfigFixture default/mageos_netsuite/stock/update_stocks_every_n_minutes 0
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_stored_at_location_level 1
     * @magentoConfigFixture default/mageos_netsuite/stock/custom_search_id 100
     * @magentoConfigFixture default/mageos_netsuite/stock/qty_field_name quantityOnHand
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/products_conf.php
     * @magentoDbIsolation enabled
     */
    public function testConfigurableStockUpdate() : void
    {
        $productRepository = $this->objectManager->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
        $stockRegistry = $this->objectManager->get(\Magento\CatalogInventory\Api\StockRegistryInterface::class);

        $product1 = $productRepository->get('simple1');
        $stock1 = $stockRegistry->getStockItem($product1->getId());
        $product2 = $productRepository->get('simple2');
        $stock2 = $stockRegistry->getStockItem($product2->getId());
        $confProduct = $productRepository->get('conf');
        $stockStatus = $stockRegistry->getStockStatus($confProduct->getId());
        // Validate DataFixture before checking anything further. 
        // Both simple have qty (10 and 5 correspondingly)
        // Both children assigned to configurable
        // Configurable is in stock,
        $this->assertEquals(10, $stock1->getQty());
        $this->assertEquals(5, $stock2->getQty());
        $children = $confProduct->getTypeInstance()->getUsedProductIds($confProduct);
        $this->assertEquals(2, count($children));
        $this->assertEquals(1, $stockStatus->getStockStatus());
        
        // Run import first time, both simples go out of stock
        $parameters = [
            'search_success' => 1,
            'suffix' => 'Conf1'
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $nsRepositoryHelper = $this->getMockBuilder(Repository::class)
            ->onlyMethods(['mapNetSuiteIdsToProductIds'])
            ->setConstructorArgs(
                [
                    $this->objectManager->create(\Magento\Framework\Api\SearchCriteriaBuilderFactory::class),
                    $this->objectManager->create(\Magento\Framework\App\ResourceConnection::class),
                    $this->objectManager->create(\Magento\Catalog\Api\ProductRepositoryInterface::class),
                    $this->objectManager->create(\Magento\Eav\Model\Entity\Attribute::class),
                    $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Helper\EavHelper::class),
                ]
            )
            ->getMock();
        $nsRepositoryHelper->method('mapNetSuiteIdsToProductIds')
            ->willReturn([
                '1' => ['entity_id' => '11', 'type_id' => '4', 'sku' => 'simple1'],
                '2' => ['entity_id' => '12', 'type_id' => '4', 'sku' => 'simple2'],
            ]);
        $this->objectManager->configure([Repository::class => ['shared' => true]]);
        $this->objectManager->addSharedInstance($nsRepositoryHelper, Repository::class);

        // Run the code
        /** @var \MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock $process */
        $process = $this->objectManager->get(\MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock::class);
        $process->process();

        // Clean up stock storage to force fresh data as stockItem is cached
        $stockRegistryStorage = $this->objectManager->get(\Magento\CatalogInventory\Model\StockRegistryStorage::class);
        $stockRegistryStorage->clean();

        // Confirm both simple quantity changed to zero
        // Configurable is out of stock
        $stock1 = $stockRegistry->getStockItem($product1->getId());
        $stock2 = $stockRegistry->getStockItem($product2->getId());
        $stockStatus = $stockRegistry->getStockStatus($confProduct->getId());
        $this->assertEquals(0, $stock1->getQty());
        $this->assertEquals(0, $stock2->getQty());
        $this->assertEquals(0, $stockStatus->getStockStatus());

        // One more run, one simple goes in stock
        $parameters = [
            'search_success' => 1,
            'suffix' => 'Conf2'
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $process->process();

        // Clean up stock storage to force fresh data as stockItem is cached
        $stockRegistryStorage->clean();

        // Confirm one simple quantity changed to 10
        // Configurable is in stock again
        $stock1 = $stockRegistry->getStockItem($product1->getId());
        $stock2 = $stockRegistry->getStockItem($product2->getId());
        $stockStatus = $stockRegistry->getStockStatus($confProduct->getId());
        $this->assertEquals(10, $stock1->getQty());
        $this->assertEquals(0, $stock2->getQty());
        $this->assertEquals(1, $stockStatus->getStockStatus());
    }
}
