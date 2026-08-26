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

namespace MageOS\NetSuiteConnector\Invoice\Test\Integration\Observer;

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;
use MageOS\NetSuiteConnector\Invoice\Model\Process\Export\InvoiceSave;

class InvoiceSaveAfterObserverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    /**
     * Path to _files/_files_ns/... folders
     */
    private const RELATIVE_PATH_TO_FIXTURES = '../';

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
            '_files/order_rollback.php',
            '_files/invoice.php',
            '_files/invoice_rollback.php'
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    protected function setUp():void
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->objectManager = $objectManager;
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/orders/discount_item_id 123
     * @magentoConfigFixture default/mageos_netsuite/tax/tax_item_internal_netsuite_id 124
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/order.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/invoice.php
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/send_invoices 1
     * @magentoDbIsolation enabled
     */
    public function testExecute()
    {
        $this->cleanUpQueue();

        $this->unsetSkipInvoiceExport();
        $observerMock = $this->prepareObserver();

        $invoiceSaveObserver = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Invoice\Observer\InvoiceSaveAfterObserver::class
        );
        $invoiceSaveObserver->execute($observerMock);

        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        $this->assertEquals(1, count($messages));
        /** @var MessageInterface $invoiceMessage */
        $invoiceMessage = array_shift($messages);

        $this->assertInstanceOf(MessageInterface::class, $invoiceMessage);
        $this->assertEquals($this->loadInvoice()->getId(), $invoiceMessage->getItemId());
        $this->assertEquals(InvoiceSave::MESSAGE_ACTION, $invoiceMessage->getAction());
        $this->assertNotNull($invoiceMessage);
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/orders/discount_item_id 123
     * @magentoConfigFixture default/mageos_netsuite/tax/tax_item_internal_netsuite_id 124
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/order.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/invoice.php
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 0
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/send_invoices 1
     * @magentoDbIsolation enabled
     */
    public function testExecuteIsEnabled()
    {
        $this->cleanUpQueue();

        $this->unsetSkipInvoiceExport();
        $observerMock = $this->prepareObserver();

        $invoiceSaveObserver = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Invoice\Observer\InvoiceSaveAfterObserver::class
        );
        $invoiceSaveObserver->execute($observerMock);

                /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        $this->assertEquals(0, count($messages));
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/orders/discount_item_id 123
     * @magentoConfigFixture default/mageos_netsuite/tax/tax_item_internal_netsuite_id 124
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/order.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/invoice.php
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/send_invoices 0
     * @magentoDbIsolation enabled
     */
    public function testExecuteIsFeatureEnabled()
    {
        $this->cleanUpQueue();
        $this->unsetSkipInvoiceExport();
        $observerMock = $this->prepareObserver();

        $invoiceSaveObserver = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Invoice\Observer\InvoiceSaveAfterObserver::class
        );
        $invoiceSaveObserver->execute($observerMock);

        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        $this->assertEquals(0, count($messages));
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/orders/discount_item_id 123
     * @magentoConfigFixture default/mageos_netsuite/tax/tax_item_internal_netsuite_id 124
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/order.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/invoice.php
     *
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/send_invoices 1
     * @magentoDbIsolation enabled
     */
    public function testExecuteSkipExport()
    {
        $this->cleanUpQueue();

        $this->unsetSkipInvoiceExport();
        $observerMock = $this->prepareObserver();

        $invoiceId = $this->loadInvoice()->getId();
        $registry = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry::class);
        $registry->register('skip_invoice_export_queue_push_' . $invoiceId, true);

        $invoiceSaveObserver = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Invoice\Observer\InvoiceSaveAfterObserver::class
        );
        $invoiceSaveObserver->execute($observerMock);

        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        $this->assertEquals(0, count($messages));
    }

    /**
     * @magentoConfigFixture default/mageos_netsuite/orders/discount_item_id 123
     * @magentoConfigFixture default/mageos_netsuite/tax/tax_item_internal_netsuite_id 124
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/order.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Invoice/Test/Integration/_files/invoice.php
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/send_invoices 1
     * @magentoDbIsolation enabled
     */
    public function testExecuteAlreadyExported()
    {
        $this->cleanUpQueue();

        $this->unsetSkipInvoiceExport();
        $invoice = $this->loadInvoice();
        $invoice->setData('netsuite_internal_id', 11111);
        $observerMock = $this->prepareObserver($invoice);
        $invoiceSaveObserver = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Invoice\Observer\InvoiceSaveAfterObserver::class
        );
        $invoiceSaveObserver->execute($observerMock);

        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        $this->assertEquals(0, count($messages));
    }

    /**
     * Unset flag to make it possible to run logic in observer
     *
     * This flag is set inside invoice.php fixture to skip observer when saving invoice from fixture
     */
    private function unsetSkipInvoiceExport()
    {
        $registry = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry::class);
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
        $invoice = $order->getInvoiceCollection()->getFirstItem();
        return $invoice;
    }

    /**
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return \PHPUnit\Framework\MockObject\MockObject
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareObserver($invoice = null): \Magento\Framework\Event\Observer
    {
        if (is_null($invoice)) {
            $invoice = $this->loadInvoice();
        }
        $registry = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry::class);
        $registry->unregister('skip_invoice_export_queue_push_' . $invoice->getId());

        $event = $this->objectManager->create(\Magento\Framework\Event::class);
        $event->setData('invoice', $invoice);

        /** @var \Magento\Framework\Event\Observer $observer */
        $observer = $this->objectManager->create(\Magento\Framework\Event\Observer::class);
        $observer->setEvent($event);

        return $observer;
    }

    /**
     * @return array
     */
    private function cleanUpQueue(): void
    {
        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        foreach ($messages as $message) {
            $messageManagement->deleteById($message->getId());
        }
    }
}
