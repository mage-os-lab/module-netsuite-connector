<?php declare(strict_types=1);
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

namespace MageOS\NetSuiteConnector\Queue\Test\Integration\Model\Queue;

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\NetSuiteConnector\Core\Exception\ConnectorRuntimeException;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;

/**
 * @magentoDbIsolation enabled
 */
class AddToQueueTest extends \PHPUnit\Framework\TestCase
{
    private const ORDER_PLACE_ACTION = 'order_place';

    /**
     * Requires NetSuiteOrder module to be installed too
     * The processor is validated inside \MageOS\NetSuiteConnector\Queue\Model\Queue\MessageManagement::validateAction
     *
     * @magentoDbIsolation enabled
     */
    public function testAddToQueueSuccess() : void
    {
        $objectManager = Bootstrap::getObjectManager(); // @phpstan-ignore-line

        $messageManagement = $objectManager->get(\MageOS\NetSuiteConnector\Queue\Model\Queue\MessageManagement::class);
        $message1 = $this->prepareValidMessage();
        $message2 = $this->prepareValidMessage();
        $messageManagement->addMessageToQueue($message1);
        $messageManagement->addMessageToQueue($message2);
        $testItemIds = [$message1[1], $message2[1]];
        $messages = $messageManagement->receive(Queue::EXPORT(), 100);
        $testMessages = array_values(array_filter(
            $messages,
            fn($m) => in_array((int)$m->getItemId(), $testItemIds, true)
        ));
        $this->assertEquals(2, count($testMessages));
        $this->assertEquals(self::ORDER_PLACE_ACTION, $testMessages[0]->getAction());
        $this->assertEquals(\MageOS\NetSuiteConnector\Core\Enum\Message\Status::IN_QUEUE(), $testMessages[0]->getStatus());
        $messagesImport = $messageManagement->receive(Queue::IMPORT(), 10);
        $this->assertEquals(0, count($messagesImport));
    }

    public function testAddToQueueInvalidAction() : void
    {
        $objectManager = Bootstrap::getObjectManager(); // @phpstan-ignore-line
        
        $messageManagement = $objectManager->get(\MageOS\NetSuiteConnector\Queue\Model\Queue\MessageManagement::class);
        $e = null;
        try {
            $messageManagement->addMessageToQueue($this->prepareInvalidMessage(
                'invalid_action',
                Queue::EXPORT(),
            ));
        } catch (\Exception $e) {
            $this->assertEquals(get_class($e), ConnectorRuntimeException::class);
            $this->assertEquals(strpos($e->getMessage(), 'Incorrect action passed: invalid_action'), 0);
        }
        $this->assertNotEquals($e, null);
    }

    public function testAddToQueueInvalidQueue() : void
    {
        $objectManager = Bootstrap::getObjectManager(); // @phpstan-ignore-line
        
        $messageManagement = $objectManager->get(\MageOS\NetSuiteConnector\Queue\Model\Queue\MessageManagement::class);
        $e = null;
        try {
            $messageManagement->addMessageToQueue($this->prepareInvalidMessage(
                self::ORDER_PLACE_ACTION,
                Queue::IMPORT()
            ));
        } catch (\Exception $e) {
            $this->assertEquals(get_class($e), ConnectorRuntimeException::class);
            $this->assertEquals(strpos($e->getMessage(), 'Class '), 0);
        }
        $this->assertNotEquals($e, null);
    }

    /**
     * Expected array 0 => action, 1 => item_id, 2 => queueName, 3 => serializableObject = null
     * @return array
     */
    private function prepareValidMessage() : array
    {
        return [
            0 => self::ORDER_PLACE_ACTION,
            1 => random_int(10000, 99999),
            2 => Queue::EXPORT()
        ];
    }

    /**
     * Expected array 0 => action, 1 => item_id, 2 => queueName, 3 => serializableObject = null
     *
     * @param string $action
     * @param Queue $queue
     * @return array
     */
    private function prepareInvalidMessage(string $action, Queue $queue) : array
    {
        return [
            0 => $action,
            1 => random_int(10000, 99999),
            2 => $queue
        ];
    }
}
