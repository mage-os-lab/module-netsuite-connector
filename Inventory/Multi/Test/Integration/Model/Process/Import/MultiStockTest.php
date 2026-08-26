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
namespace MageOS\NetSuiteConnector\Inventory\Multi\Test\Integration\Model\Process\Import;

use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management as ServiceManagement;
use MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;

/**
 * Class MultiStockTest -
 * @SuppressWarnings(PHPMD)
 */
class MultiStockTest extends \PHPUnit\Framework\TestCase
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
     * Path to _files/_files_ns/... folders
     */
    const RELATIVE_PATH_TO_FIXTURES = '../../../';

    public static function setUpBeforeClass():void
    {
        $fixturesUsed = [
            '_files_ns_response/GetServerTimeRequest',
            '_files_ns_response/ItemSearchAdvanced',
            '_files/locations.php',
            '_files/locations_rollback.php',
            '_files/product_simple.php',
            '_files/product_simple_rollback.php'
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
                        $this->objectManager->create(
                            \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\ConstructorFactory::class
                        ),
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
     * Update all sources
     *
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/general/inventory_mode multi
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_stock_updates 1
     * @magentoConfigFixture default/mageos_netsuite/stock/update_stocks_every_n_minutes 0
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_stored_at_location_level 1
     * @magentoConfigFixture default/mageos_netsuite/stock/custom_search_id 100
     * @magentoConfigFixture default/mageos_netsuite/stock/qty_field_name quantityOnHand
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/locations.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/product_simple.php
     * @magentoDbIsolation enabled
     */
    public function testProcessMultiStockImport()
    {
        $getSourceItemsBySku = $this->objectManager->get(\Magento\InventoryApi\Api\GetSourceItemsBySkuInterface::class);
        $productSources = $getSourceItemsBySku->execute('simple');

        /**
         * Validate DataFixture before checking anything further
         */
        foreach ($productSources as $productSource) {
            if ($productSource->getSourceCode() === 'source_1') {
                $this->assertEquals(10, (int)$productSource->getQuantity());
                $this->assertEquals(1, (int)$productSource->getStatus());
            }

            if ($productSource->getSourceCode() === 'source_2') {
                $this->assertEquals(0, (int)$productSource->getQuantity());
                $this->assertEquals(0, (int)$productSource->getStatus());
            }
        }

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

        /**
         * Run the code
         */
        /** @var \MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock $process */
        $process = $this->objectManager->get(\MageOS\NetSuiteConnector\Inventory\Model\Process\Import\Stock::class);
        $process->process();

        /**
         * Confirm the quantity changed, get the stock item again
         */
        $productSources = $getSourceItemsBySku->execute('simple');
        foreach ($productSources as $productSource) {
            if ($productSource->getSourceCode() === 'source_1') {
                $this->assertEquals(10, (int)$productSource->getQuantity());
                $this->assertEquals(1, (int)$productSource->getStatus());
            }

            if ($productSource->getSourceCode() === 'source_2') {
                $this->assertEquals(20, (int)$productSource->getQuantity());
                $this->assertEquals(1, (int)$productSource->getStatus());
            }
        }
    }

    /**
     * Flow:
     * 1. First source - decrease qty to 0 and change status to 0
     * 2. Second source - leave qty and status without changes
     *
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/general/inventory_mode multi
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/get_stock_updates 1
     * @magentoConfigFixture default/mageos_netsuite/stock/update_stocks_every_n_minutes 0
     * @magentoConfigFixture default/mageos_netsuite/stock/stock_stored_at_location_level 1
     * @magentoConfigFixture default/mageos_netsuite/stock/custom_search_id 100
     * @magentoConfigFixture default/mageos_netsuite/stock/qty_field_name quantityOnHand
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/locations.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/product_simple.php
     * @magentoDbIsolation enabled
     */
    public function testProcessMultiStockImportDecreaseQty()
    {
        $getSourceItemsBySku = $this->objectManager->get(\Magento\InventoryApi\Api\GetSourceItemsBySkuInterface::class);
        $productSources = $getSourceItemsBySku->execute('simple');

        /**
         * Validate DataFixture before checking anything further
         */
        foreach ($productSources as $productSource) {
            if ($productSource->getSourceCode() === 'source_1') {
                $this->assertEquals(10, (int)$productSource->getQuantity());
                $this->assertEquals(1, (int)$productSource->getStatus());
            }

            if ($productSource->getSourceCode() === 'source_2') {
                $this->assertEquals(0, (int)$productSource->getQuantity());
                $this->assertEquals(0, (int)$productSource->getStatus());
            }
        }

        /**
         * Set NS response values
         */
        $parameters = [
            'search_success' => 1,
            'record' => 'decrease_qty'
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
         * Confirm the quantity changed, get the stock item again
         */
        $productSources = $getSourceItemsBySku->execute('simple');
        foreach ($productSources as $productSource) {
            if ($productSource->getSourceCode() === 'default') {
                continue;
            }
            $this->assertEquals(0, (int)$productSource->getQuantity());
            $this->assertEquals(0, (int)$productSource->getStatus());
        }
    }
}
