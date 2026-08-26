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
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Enum\Message\Status;

/**
 * @magentoDbIsolation enabled
 */
class ReceiveQueueTest extends \PHPUnit\Framework\TestCase
{
    private const ORDER_PLACE_ACTION = 'order_place';

    /**
     * Requires NetSuiteOrder module to be installed too
     * The processor is validated inside \MageOS\NetSuiteConnector\Queue\Model\Queue\MessageManagement::validateAction
     */
    public function testReceiveQueue() : void
    {
        $objectManager = Bootstrap::getObjectManager(); // @phpstan-ignore-line

        /** @var \Magento\Framework\App\ResourceConnection $resource */
        $resource = $objectManager->get(\Magento\Framework\App\ResourceConnection::class);
        $connection = $resource->getConnection();
        $connection->delete($resource->getTableName('mageos_netsuite_monitor'));
        $connection->delete($resource->getTableName('mageos_netsuite_message'));

        $messageManagement = $objectManager->get(\MageOS\NetSuiteConnector\Queue\Model\Queue\MessageManagement::class);
        for ($i=0; $i<5; $i++) {
            $messageManagement->addMessageToQueue($this->prepareValidMessage());
        }
        $messages = $messageManagement->receive(Queue::EXPORT(), 10);
        $this->assertEquals(5, count($messages));
        $messageManagement->changeStatus([$messages[0]->getId(), $messages[1]->getId()], Status::DONE());
        $messageManagement->changeStatus([$messages[2]->getId()], Status::ERROR(), 'some error');
        $messageManagement->changeStatus([$messages[3]->getId(), $messages[4]->getId()], Status::RETRY(), 'retry');
        
        $messagesImport = $messageManagement->receive(Queue::EXPORT(), 10);
        // Done and Error messages are not pulled, so only 2 Retry messages are received
        $this->assertEquals(2, count($messagesImport));
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
}
