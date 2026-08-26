<?php

namespace MageOS\NetSuiteConnector\Invoice\Test\Integration\Model\Process\Import;

use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\Order\Invoice;
use Magento\TestFramework\Helper\Bootstrap;
use NetSuite\Classes\CashSale;
use NetSuite\Classes\CashSaleItem;
use NetSuite\Classes\CashSaleItemList;
use NetSuite\Classes\RecordRef;
use MageOS\NetSuiteConnector\Core\Exception\SkipRecordException;
use MageOS\NetSuiteConnector\Core\Test\Integration\Model\NSRecordBuilder;

/**
 * Class CashsaleTest -
 * @magentoDbIsolation enabled
 */
class CashsaleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private static $nsHelper;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker
     */
    private static $netsuiteServiceFaker;
    /**
     * Path to _files/_files_ns/... folders
     */
    private const RELATIVE_PATH_TO_FIXTURES = '../../../';
    /**
     * Clean up invoice and order cache
     */
    protected function tearDown(): void
    {
        $this->cleanupInvoiceCache();
        $this->cleanupOrderCache();
        parent::tearDown();
    }

    /**
     * $netusiteServicerFaker is a replacement class for WSDL Netsuite class
     * $nsHelper is a mock because we use getNetsuiteService() call to get access to WSDL Netsuite class
     *
     * Because how Magento & phpunit works, we need to have them as static values. Main reason - the Mock is
     * cached but on the second test we create a new instance of the Mock but the old one is actually still active
     * in Magento code.
     */
    protected function setUp(): void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->objectManager = $objectManager;

        if (!self::$netsuiteServiceFaker) {
            $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";
            self::$netsuiteServiceFaker = new \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker($path);
        }

        if (!self::$nsHelper) {
            self::$nsHelper = $this->getMockBuilder(\MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class)
                ->onlyMethods(['get'])
                ->disableOriginalConstructor()
                ->getMock();
        }

        $this->objectManager->configure([
            \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class => ['shared' => true]
        ]);
        $this->objectManager->addSharedInstance(
            self::$nsHelper,
            \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class
        );
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testThatItSkipsInvalidRecords()
    {
        $objectManager = Bootstrap::getObjectManager();

        $cashSale = NSRecordBuilder::aRecord(CashSale::class)
            ->withInternalId(1001)
            ->build();

        /** @var \MageOS\NetSuiteConnector\Invoice\Model\Process\Import\Cashsale $cashSaleImport */
        $cashSaleImport = $objectManager->get(\MageOS\NetSuiteConnector\Invoice\Model\Process\Import\Cashsale::class);

        $this->assertFalse($cashSaleImport->isMagentoImportable($cashSale));
        $this->assertFalse($cashSaleImport->isAlreadyImported($cashSale));

        $this->expectException(SkipRecordException::class);

        $cashSaleImport->process($cashSale);
    }

    /**
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/order.php
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/order_set_netsuite_id.php
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/invoice_import.php
     *
     * @magentoDbIsolation enabled
     * @magentoAppIsolation enabled
     */
    public function testThatItImportsTheInvoice()
    {
        $parameters = [
            'netsuite_internal_id' => 11,
            'get_success' => 1
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();
        $objectManager = Bootstrap::getObjectManager();

        $cashSale = NSRecordBuilder::aRecord(CashSale::class)
            ->withInternalId(1001)
            ->withCreatedFrom(1)
            ->withItemList($this->createCashsaleItemList())
            ->withTotal(20)
            ->withSubTotal(20)
            ->withEntity($this->recordRef(1))
            ->build();

        /** @var \MageOS\NetSuiteConnector\Invoice\Model\Process\Import\Cashsale $cashSaleImport */
        $cashSaleImport = $objectManager->get(\MageOS\NetSuiteConnector\Invoice\Model\Process\Import\Cashsale::class);
        $invoiceRegistry = $objectManager->get(\MageOS\NetSuiteConnector\Invoice\Model\InvoiceRegistry::class);

        $this->assertTrue($cashSaleImport->isMagentoImportable($cashSale));
        $this->assertFalse($cashSaleImport->isAlreadyImported($cashSale));

        $cashSaleImport->process($cashSale);

        /** @var Invoice $invoice */
        $invoice = $invoiceRegistry->getInvoiceByNetSuiteId(1001);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals('20.0000', $invoice->getSubtotal());
        $this->assertEquals('1', $invoice->getStoreId());
        $this->assertCount(1, $invoice->getItems());
        $this->assertEquals(2, $invoice->getTotalQty());

        $items = $invoice->getItems();
        $firstKey = array_keys($items)[0];
        $firstItem = $items[$firstKey];

        $this->assertEquals('simple', $firstItem->getSku());
        $this->assertEquals(10, $firstItem->getPrice());
        $this->assertEquals(20, $firstItem->getRowTotal());
        $this->assertEquals(2, $firstItem->getQty());

        $shippingAddress = $invoice->getShippingAddress();
        $this->validateAddress($shippingAddress);

        $billingAddress = $invoice->getBillingAddress();
        $this->validateAddress($billingAddress);
    }

    /**
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/order_with_configurable.php
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/order_set_netsuite_id.php
     * @magentoDataFixtureBeforeTransaction ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/invoice_import.php
     * @magentoAppIsolation enabled
     */
    public function testThatItImportsInvoiceWithConfigurable()
    {
        $parameters = [
            'netsuite_internal_id' => 11,
            'get_success' => 1
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();
        $objectManager = Bootstrap::getObjectManager();

        $cashSale = NSRecordBuilder::aRecord(CashSale::class)
            ->withInternalId(1001)
            ->withCreatedFrom(1)
            ->withItemList($this->createCashsaleConfigurableItemList())
            ->withTotal(20)
            ->withSubTotal(20)
            ->withEntity($this->recordRef(1))
            ->build();

        /** @var \MageOS\NetSuiteConnector\Invoice\Model\Process\Import\Cashsale $cashSaleImport */
        $cashSaleImport = $objectManager->get(\MageOS\NetSuiteConnector\Invoice\Model\Process\Import\Cashsale::class);
        $invoiceRegistry = $objectManager->get(\MageOS\NetSuiteConnector\Invoice\Model\InvoiceRegistry::class);

        $this->assertTrue($cashSaleImport->isMagentoImportable($cashSale));
        $this->assertFalse($cashSaleImport->isAlreadyImported($cashSale));

        $cashSaleImport->process($cashSale);

        /** @var Invoice $invoice */
        $invoice = $invoiceRegistry->getInvoiceByNetSuiteId(1001);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals('20.0000', $invoice->getSubtotal());
        $this->assertEquals('1', $invoice->getStoreId());
        $this->assertCount(2, $invoice->getItems());
        $this->assertEquals(2, $invoice->getTotalQty());

        $items = $invoice->getAllItems();
        $firstKey = array_keys($items)[0];
        /** @var \Magento\Sales\Model\Order\Invoice\Item $firstItem */
        $firstItem = $items[$firstKey];

        $this->assertEquals('configurable', $firstItem->getSku());
        $this->assertEquals(10, $firstItem->getPrice());
        $this->assertEquals('20.0000', $firstItem->getRowTotal());
        $this->assertEquals(2, $firstItem->getQty());

        $secondKey = array_keys($items)[1];
        /** @var \Magento\Sales\Model\Order\Invoice\Item $secondItem */
        $secondItem = $items[$secondKey];

        $this->assertEquals('simple_10', $secondItem->getSku());
        $this->assertEquals('10.0000', $secondItem->getPrice());
        $this->assertEquals(null, $secondItem->getRowTotal());
        $this->assertEquals(2, $secondItem->getQty());

        $shippingAddress = $invoice->getShippingAddress();
        $this->validateAddress($shippingAddress);

        $billingAddress = $invoice->getBillingAddress();
        $this->validateAddress($billingAddress);
    }

    /**
     * @param $internalId
     * @return RecordRef
     */
    private function recordRef($internalId): RecordRef
    {
        $recordRef = new RecordRef;
        $recordRef->internalId = (string)$internalId;
        return $recordRef;
    }

    /**
     * @param $shippingAddress
     */
    private function validateAddress(Address $shippingAddress)
    {
        $this->assertEquals('US', $shippingAddress->getCountryId());
        $this->assertEquals('11111', $shippingAddress->getPostcode());
        $this->assertEquals(['street'], $shippingAddress->getStreet());
        $this->assertEquals('CA', $shippingAddress->getRegionCode());
        $this->assertEquals('customer@null.com', $shippingAddress->getEmail());
    }

    private function createCashsaleItemList()
    {
        $item1 = new CashSaleItem();
        $item1->item = $this->recordRef(1);
        $item1->quantity = 2;
        $item1->rate = 10.0;
        $item1->taxRate1 = 0.0;

        $itemList = new CashSaleItemList();
        $itemList->item = [
            $item1
        ];

        return $itemList;
    }

    private function createCashsaleConfigurableItemList()
    {
        $item1 = new CashSaleItem();
        $item1->item = $this->recordRef(10);
        $item1->quantity = 2;
        $item1->rate = 10.0;
        $item1->taxRate1 = 0.0;

        $itemList = new CashSaleItemList();
        $itemList->item = [
            $item1
        ];

        return $itemList;
    }

    protected function cleanupInvoiceCache()
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var \MageOS\NetSuiteConnector\Invoice\Model\InvoiceRegistry $invoiceRegistry */
        $invoiceRegistry = $objectManager->get(\MageOS\NetSuiteConnector\Invoice\Model\InvoiceRegistry::class);

        $refObject = new \ReflectionObject($invoiceRegistry);

        $refProperty = $refObject->getProperty('invoiceCache');
        $refProperty->setValue($invoiceRegistry, []);
    }

    protected function cleanupOrderCache()
    {
        $objectManager = Bootstrap::getObjectManager();

        /** @var \MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface $orderRegistry */
        $orderRegistry = $objectManager->get(\MageOS\NetSuiteConnector\Order\Api\OrderRegistryInterface::class);

        $refObject = new \ReflectionObject($orderRegistry);

        $refProperty = $refObject->getProperty('orderCache');
        $refProperty->setValue($orderRegistry, []);
    }

    private function setNetSuiteServiceFaker()
    {
        self::$nsHelper->method('get')
            ->willReturn(self::$netsuiteServiceFaker);
    }
}
