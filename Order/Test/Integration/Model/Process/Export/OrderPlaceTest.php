<?php

namespace MageOS\NetSuiteConnector\Order\Test\Integration\Model\Process\Export;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Helper\Bootstrap;
use NetSuite\Classes\AddRequest;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Test\Integration\Fixtures\Locator;

/**
 * Class OrderPlaceTest -
 * @magentoDbIsolation enabled
 * @magentoConfigFixture default/payment/checkmo/active 1
 */
class OrderPlaceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Path to _files/_files_ns/... folders
     */
    private const RELATIVE_PATH_TO_FIXTURES = '../../../';

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    private $objectManager;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Helper\Data|\PHPUnit\Framework\MockObject\MockObject
     */
    private static $nsHelper;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker
     */
    private static $netsuiteServiceFaker;

    /**
     * @var array
     */
    private $parameters = [
        'by_field' => 'externalIdString',
        'search_success' => 0,
        'netsuite_internal_id' => 2,
        'get_success' => 1,
        'add_success' => 1
    ];

    /**
     * {@inheritDoc}
     */
    public static function setUpBeforeClass():void
    {
        $fixturesUsed = [
            '_files/default_rollback.php',
            '_files/product_simple.php',
            '_files/address_data.php',
            '_files/customer.php',
            '_files/order.php',
            '_files/order_with_discount.php',
            '_files/submit_order_to_ns_queue.php',
            '_files/quote.php',
            '_files/quote_rollback.php',
            '_files/quote_with_configurable_product_rollback.php',
            '_files/quote_with_configurable_product.php',
        ];

        $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";

        Locator::copy($path, $fixturesUsed);
    }

    /**
     * {@inheritDoc}
     */
    protected function setUp():void
    {
        $this->objectManager = Bootstrap::getObjectManager();

        if (!self::$netsuiteServiceFaker) {
            $path = realpath(__DIR__ . "/" . self::RELATIVE_PATH_TO_FIXTURES) . "/";
            self::$netsuiteServiceFaker = new \MageOS\NetSuiteConnector\Core\Test\Integration\Helper\NetSuiteServiceFaker($path);
        }

        if (!self::$nsHelper) {
            self::$nsHelper = $this->getMockBuilder(\MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class)
                ->onlyMethods(['get'])
                ->setConstructorArgs(
                    [
                        $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry::class),
                        $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig::class),
                        $this->objectManager->create(
                            \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\ConstructorFactory::class
                        )
                    ]
                )
                ->getMock();
        }

        $this->objectManager->configure([
            \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class => ['shared' => true]
        ]);
        $this->objectManager->addSharedInstance(
            self::$nsHelper,
            \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management::class
        );
        self::$netsuiteServiceFaker->setParameters($this->parameters);
        $this->setNetSuiteFaker();
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/quote.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/order.php
     * phpcs:disable
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/submit_order_to_ns_queue.php
     * @magentoConfigFixture default/mageos_netsuite/payment_methods/netsuite_mapping [{"payment_method":"checkmo","payment_cc":"","internal_netsuite_id":"1"}]
     * @magentoConfigFixture default/mageos_netsuite/shipping_methods/netsuite_default_shipping_id 2
     * @magentoConfigFixture default/mageos_netsuite/shipping_methods/netsuite_mapping {"_1598626367813_813":{"shipping_method":"flatrate_flatrate","shipping_description":"","internal_netsuite_id":"2"}}
     * phpcs:enable
     * @magentoConfigFixture default/mageos_netsuite/orders/location_id 2
     * @magentoConfigFixture default/mageos_netsuite/orders/logic_switch line
     * @magentoAppIsolation enabled
     */
    public function testOrderExport()
    {
        \Magento\TestFramework\Helper\Bootstrap::getInstance()
            ->loadArea(\Magento\Framework\App\Area::AREA_FRONTEND);

        $message = $this->getMessage();
        $orderPlaceProcess = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Order\Model\Process\Export\OrderPlace::class
        );
        $orderPlaceProcess->process($message);

        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $order = $orderRepository->get($message->getItemId());

        $nsIdAttributeValue = $order->getNetsuiteInternalId();
        $this->assertEquals($this->parameters['netsuite_internal_id'], $nsIdAttributeValue);

        /** @var AddRequest $addRequest */
        $addRequest = self::$netsuiteServiceFaker->getAddRequest();
        $this->assertNotNull($addRequest);

        $expectedAddRequest = $this->getRequest('Order-new');
        unset(
            $addRequest->record->tranDate,
            $expectedAddRequest->record->tranDate,
            $addRequest->record->shippingTaxCode,
            $expectedAddRequest->record->shippingTaxCode
        );
        $this->assertEquals($expectedAddRequest->record, $addRequest->record);

        $this->cleanUpQueue();
    }

    /**
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/customer.php
     * phpcs:disable
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/quote_with_configurable_product.php
     * @magentoDataFixture Magento/ConfigurableProduct/_files/order_item_with_configurable_and_options.php
     * @magentoDataFixture ../../../../app/code/MageOS/NetSuiteConnector/Core/Test/Integration/_final/_files/submit_order_to_ns_queue.php
     * @magentoConfigFixture default/mageos_netsuite/payment_methods/netsuite_mapping [{"payment_method":"checkmo","payment_cc":"","internal_netsuite_id":"1"}]
     * @magentoConfigFixture default/mageos_netsuite/shipping_methods/netsuite_default_shipping_id 2
     * @magentoConfigFixture default/mageos_netsuite/shipping_methods/netsuite_mapping {"_1598626367813_813":{"shipping_method":"flatrate_flatrate","shipping_description":"","internal_netsuite_id":"2"}}
     * phpcs:enable
     * @magentoConfigFixture default/mageos_netsuite/orders/location_id 2
     * @magentoConfigFixture default/mageos_netsuite/orders/logic_switch line
     * @magentoConfigFixture default/payment/checkmo/active 1
     * @magentoAppIsolation enabled
     */
    public function testOrderWithConfigurable()
    {
        \Magento\TestFramework\Helper\Bootstrap::getInstance()
            ->loadArea(\Magento\Framework\App\Area::AREA_FRONTEND);

        $message = $this->getMessage();
        $orderPlaceProcess = $this->objectManager->create(
            \MageOS\NetSuiteConnector\Order\Model\Process\Export\OrderPlace::class
        );
        $orderPlaceProcess->process($message);

        $orderRepository = $this->objectManager->get(OrderRepositoryInterface::class);
        $order = $orderRepository->get($message->getItemId());

        $nsIdAttributeValue = $order->getNetsuiteInternalId();
        $this->assertEquals($this->parameters['netsuite_internal_id'], $nsIdAttributeValue);

        /** @var AddRequest $addRequest */
        $addRequest = self::$netsuiteServiceFaker->getAddRequest();
        $this->assertNotNull($addRequest);

        $expectedAddRequest = $this->getRequest('OrderConfigurable-new');
        unset(
            $addRequest->record->tranDate,
            $expectedAddRequest->record->tranDate,
            $addRequest->record->shippingTaxCode,
            $expectedAddRequest->record->shippingTaxCode
        );
        $this->assertEquals($expectedAddRequest->record, $addRequest->record);

        $this->cleanUpQueue();
    }

    /**
     * Create Message which we process
     *
     * @return MessageInterface
     */
    private function getMessage(): MessageInterface
    {
        // Based on the fixture _files/order.php and as every new order creates with new entity_id,
        // the current message is loaded from messages' collection.
        /** @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement */
        $messageManagement = $this->objectManager->create(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
        $messages = $messageManagement->receive(\MageOS\NetSuiteConnector\Core\Enum\Message\Queue::EXPORT(), 50);
        $this->assertCount(1, $messages);

        foreach ($messages as $originalMessage) {
            $message = $originalMessage;
        }

        return $message;
    }

    /**
     * Helper methods for tests
     */
    private function setNetSuiteFaker()
    {
        self::$nsHelper->method('get')
            ->willReturn(self::$netsuiteServiceFaker);
    }

    /**
     * Fetch expected request from file for comparison
     *
     * @param $fileName
     * @return mixed
     */
    private function getRequest(string $fileName)
    {
        $file = __DIR__ . "/../../../_files_ns_request/" . $fileName;
        // phpcs:ignore
        return unserialize(rtrim(file_get_contents($file)));
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
