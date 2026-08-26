<?php

namespace MageOS\NetSuiteConnector\Order\Test\Integration\Observer;

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;
use MageOS\NetSuiteConnector\Order\Model\Process\Export\OrderPlace;

/**
 * Class OrderPlaceObserverTest -
 * @magentoDbIsolation enabled
 */
class OrderPlaceObserverTest extends \PHPUnit\Framework\TestCase
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
            '_files/default_rollback.php',
            '_files/product_simple.php',
            '_files/address_data.php',
            '_files/customer.php',
            '_files/order.php',
            '_files/order_with_discount.php',
            '_files/quote.php',
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    protected function setUp():void
    {
        $this->objectManager = Bootstrap::getObjectManager();
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/quote.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order.php
     * phpcs:disable
     * @magentoConfigFixture default/mageos_netsuite/payment_methods/netsuite_mapping [{"payment_method":"checkmo","payment_cc":"","internal_netsuite_id":"1"}]
     * phpcs:enable
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/send_orders 1
     * @magentoAppIsolation enabled
     */
    public function testExecute()
    {
        $this->cleanUpQueue();

        $observerMock = $this->prepareObserver();

        $orderPlaceObserver = $this->objectManager->create(\MageOS\NetSuiteConnector\Order\Observer\OrderPlaceObserver::class);
        $orderPlaceObserver->execute($observerMock);
        $order = $observerMock->getEvent()->getOrder();

        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        $this->assertEquals(1, count($messages));
        /** @var MessageInterface $invoiceMessage */
        $invoiceMessage = array_shift($messages);

        $this->assertInstanceOf(MessageInterface::class, $invoiceMessage);
        $this->assertEquals($order->getId(), $invoiceMessage->getItemId());
        $this->assertEquals(OrderPlace::MESSAGE_ACTION, $invoiceMessage->getAction());
        $this->assertNotNull($invoiceMessage);
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/quote.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/order.php
     * phpcs:disable
     * @magentoConfigFixture default/mageos_netsuite/payment_methods/netsuite_mapping [{"payment_method":"checkmo","payment_cc":"","internal_netsuite_id":"1"}]
     * phpcs:enable
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 0
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/send_orders 1
     * @magentoAppIsolation enabled
     */
    public function testExecuteIsNotEnabled()
    {
        $this->cleanUpQueue();

        $observerMock = $this->prepareObserver();

        $orderPlaceObserver = $this->objectManager->create(\MageOS\NetSuiteConnector\Order\Observer\OrderPlaceObserver::class);
        $orderPlaceObserver->execute($observerMock);

        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        $this->assertEquals(0, count($messages));
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/quote.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/order.php
     * phpcs:disable
     * @magentoConfigFixture default/mageos_netsuite/payment_methods/netsuite_mapping [{"payment_method":"checkmo","payment_cc":"","internal_netsuite_id":"1"}]
     * phpcs:enable
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/send_orders 0
     * @magentoAppIsolation enabled
     */
    public function testExecuteFeatureNotEnabled()
    {
        $this->cleanUpQueue();

        $observerMock = $this->prepareObserver();

        $orderPlaceObserver = $this->objectManager->create(\MageOS\NetSuiteConnector\Order\Observer\OrderPlaceObserver::class);
        $orderPlaceObserver->execute($observerMock);

        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        $this->assertEquals(0, count($messages));
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/quote.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_files/order.php
     * phpcs:disable
     * @magentoConfigFixture default/mageos_netsuite/payment_methods/netsuite_mapping [{"payment_method":"checkmo","payment_cc":"","internal_netsuite_id":"1"}]
     * phpcs:enable
     * @magentoConfigFixture default/mageos_netsuite/general/enabled 1
     * @magentoConfigFixture default/mageos_netsuite/enable_disable_features/send_orders 1
     * @magentoAppIsolation enabled
     */
    public function testExecuteSkipExport()
    {
        $this->cleanUpQueue();

        $observerMock = $this->prepareObserver();

        $registry = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry::class);
        $registry->register('netsuite_skip_order_export', true);
        $orderPlaceObserver = $this->objectManager->create(\MageOS\NetSuiteConnector\Order\Observer\OrderPlaceObserver::class);
        $orderPlaceObserver->execute($observerMock);

        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(Queue::EXPORT(), 50);
        $this->assertEquals(0, count($messages));
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function prepareObserver(): \Magento\Framework\Event\Observer
    {
        /** @var \Magento\Sales\Api\OrderRepositoryInterface $orderRepositoryInterface */
        $orderCollection = $this->objectManager->get(\Magento\Sales\Model\ResourceModel\Order\Collection::class);
        $magentoOrder = $orderCollection->getLastItem();

        $event = $this->objectManager->create(\Magento\Framework\Event::class);
        $event->setData('order', $magentoOrder);

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
