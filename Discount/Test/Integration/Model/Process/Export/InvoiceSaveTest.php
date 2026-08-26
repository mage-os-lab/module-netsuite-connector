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
 */

namespace MageOS\NetSuiteConnector\Discount\Test\Integration\Model\Process\Export;

use Magento\TestFramework\Helper\Bootstrap;
use NetSuite\Classes\AddRequest;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;

/**
 * @SuppressWarnings(PHPMD)
 */
class InvoiceSaveTest extends \PHPUnit\Framework\TestCase
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

    public static function setUpBeforeClass():void
    {
        $fixturesUsed = [
            '_files/address_data.php',
            '_files/customer.php',
            '_files/customer_rollback.php',
            '_files/default_rollback.php',
            '_files/product_simple.php',
            '_files/product_simple_rollback.php',
            '_files/order.php',
            '_files/order_set_netsuite_id.php',
            '_files/order_rollback.php',
            '_files/invoice.php',
            '_files/invoice_rollback.php'
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    /**
     * $netusiteServicerFaker is a replacement class for WSDL Netsuite class
     * $nsHelper is a mock because we use getNetsuiteService() call to get access to WSDL Netsuite class
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
        }

        if (!self::$nsHelper) {
            self::$nsHelper = $this->getMockBuilder(\MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class)
                ->onlyMethods(['get'])
                ->disableOriginalConstructor()
                ->getMock();
        }

        $this->objectManager->configure(
            [\MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class => ['shared' => true]]
        );
        $this->objectManager->addSharedInstance(
            self::$nsHelper,
            \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class
        );
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Discount/Test/Integration/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Discount/Test/Integration/_files/order_with_discount.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Discount/Test/Integration/_files/order_set_netsuite_id.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Discount/Test/Integration/_files/invoice.php
     * @magentoConfigFixture default/mageos_netsuite/orders/discount_item_id 123
     * @magentoConfigFixture default/mageos_netsuite/stock/order_skip_location 1
     * @magentoConfigFixture default/mageos_netsuite/tax/skip_tax 1
     * @magentoConfigFixture default/mageos_netsuite/orders/logic_switch line
     * @magentoDbIsolation enabled
     */
    public function testInvoiceExportLineDiscount()
    {
        $this->unsetSkipInvoiceExport();
        $parameters = [
            'netsuite_internal_id' => 11,
            'initialize_success' => 1,
            'add_success' => 1
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();

        $message = $this->getMessage();

        $invoiceSaveProcess = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Invoice\Model\Process\Export\InvoiceSave::class
        );
        $invoiceSaveProcess->process($message);

        // load the invoice again, it should have a different internal_id
        $invoice = $this->loadInvoice();

        $this->assertEquals($parameters['netsuite_internal_id'], $invoice->getData('netsuite_internal_id'));

        /** @var AddRequest $addRequest */
        $addRequest = self::$netsuiteServiceFaker->getAddRequest();
        $expectedAddRequest = $this->getRequest('InvoiceLineDiscount-new');
        // copy existing logic from original class: remove "shipGroupList" property
        $cashSale = $expectedAddRequest->record;
        unset($cashSale->shipGroupList);

        $this->assertEquals($expectedAddRequest, $addRequest);
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Discount/Test/Integration/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Discount/Test/Integration/_files/order_with_discount.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Discount/Test/Integration/_files/order_set_netsuite_id.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Discount/Test/Integration/_files/invoice.php
     * @magentoConfigFixture default/mageos_netsuite/orders/discount_item_id 123
     * @magentoConfigFixture default/mageos_netsuite/stock/order_skip_location 1
     * @magentoConfigFixture default/mageos_netsuite/tax/skip_tax 1
     * @magentoConfigFixture default/mageos_netsuite/orders/logic_switch body
     * @magentoDbIsolation enabled
     */
    public function testInvoiceExportBodyDiscount()
    {
        $this->unsetSkipInvoiceExport();
        $parameters = [
            'netsuite_internal_id' => 11,
            'initialize_success' => 1,
            'add_success' => 1
        ];
        self::$netsuiteServiceFaker->setParameters($parameters);
        $this->setNetSuiteServiceFaker();

        $message = $this->getMessage();

        $invoiceSaveProcess = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Invoice\Model\Process\Export\InvoiceSave::class
        );
        $invoiceSaveProcess->process($message);

        // load the invoice again, it should have a different internal_id
        $invoice = $this->loadInvoice();

        $this->assertEquals($parameters['netsuite_internal_id'], $invoice->getData('netsuite_internal_id'));

        /** @var AddRequest $addRequest */
        $addRequest = self::$netsuiteServiceFaker->getAddRequest();
        $expectedAddRequest = $this->getRequest('InvoiceBodyDiscount-new');
        // copy existing logic from original class: remove "shipGroupList" property
        $cashSale = $expectedAddRequest->record;
        unset($cashSale->shipGroupList);

        $this->assertEquals($expectedAddRequest, $addRequest);
    }

    /**
     * Unset flag to make it possible to run logic in observer
     *
     * This flag is set inside invoice.php fixture to skip observer when saving invoice from fixture
     */
    private function unsetSkipInvoiceExport()
    {
        $registry = $this->objectManager->get(\Magento\Framework\Registry::class);
        $registry->unregister('netsuite_skip_invoice_export');
    }

    /**
     * Get invoice created inside fixtures
     *
     * @return \Magento\Sales\Model\Order\Invoice
     */
    private function loadInvoice()
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->objectManager->create(\Magento\Sales\Model\Order::class);
        $order->loadByIncrementId('100000001');
        /** @var \Magento\Sales\Model\Order\Invoice $invoice */
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        $invoiceId = $invoice->getId();

        $invoiceRepository = $this->objectManager->create(\Magento\Sales\Api\InvoiceRepositoryInterface::class);
        $invoice = $invoiceRepository->get($invoiceId);
        return $invoice;
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
     * Create Message which we process
     *
     * @return MessageInterface
     */
    private function getMessage(): MessageInterface
    {
        /** @var MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(MessageManagementInterface::class);
        $message = $messageManagement->createMessage(
            \MageOS\NetSuiteConnector\Invoice\Model\Process\Export\InvoiceSave::MESSAGE_ACTION,
            (int)$this->loadInvoice()->getId(),
            Queue::EXPORT()
        );

        return $message;
    }

    /**
     * Fetch expected request from file for comparison
     *
     * @param $fileName
     * @return mixed
     */
    //phpcs:disable
    private function getRequest(string $fileName)
    {
        $file = __DIR__ . "/../../../_files_ns_request/" . $fileName;
        $content = file_get_contents($file);
        $serialized = rtrim(str_replace("\r", "", $content));
        return unserialize($serialized);
    }
}
