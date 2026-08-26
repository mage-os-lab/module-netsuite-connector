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
use MageOS\NetSuiteConnector\Core\Model\Config\QueueConfig;

class RejectTest extends \PHPUnit\Framework\TestCase
{
    private const ORDER_PLACE_ACTION = 'order_place';

    /**
     * Requires NetSuiteOrder module to be installed too
     * The processor is validated inside \MageOS\NetSuiteConnector\Queue\Model\Queue\MessageManagement::validateAction
     */
    public function testRejectError() : void
    {
        $objectManager = Bootstrap::getObjectManager(); // @phpstan-ignore-line
        
        $messageManagement = $objectManager->get(\MageOS\NetSuiteConnector\Queue\Model\Queue\MessageManagement::class);
        $message = $messageManagement->createMessage(
            self::ORDER_PLACE_ACTION,
            random_int(10000, 99999),
            Queue::EXPORT()
        );
        $message->setData('number_of_trials', 10);
        $messageManagement->send($message);
        $messages = $messageManagement->receive(Queue::EXPORT(), 10);
        $this->assertEquals(1, count($messages));
        $messageManagement->reject([$messages[0]->getId()], 'some fatal');
        $messagesStep2 = $messageManagement->receive(Queue::EXPORT(), 10);
        $this->assertEquals(0, count($messagesStep2));
    }

    /**
     * Requires NetSuiteOrder module to be installed too
     * The processor is validated inside \MageOS\NetSuiteConnector\Queue\Model\Queue\MessageManagement::validateAction
     */
    public function testRejectRetry() : void
    {
        $objectManager = Bootstrap::getObjectManager(); // @phpstan-ignore-line
        
        $messageManagement = $objectManager->get(\MageOS\NetSuiteConnector\Queue\Model\Queue\MessageManagement::class);
        $messageManagement->addMessageToQueue([
            0 => self::ORDER_PLACE_ACTION,
            1 => random_int(10000, 99999),
            2 => Queue::EXPORT()
        ]);
        // Running receive-reject 10 times so number of trials get 10 and message is put into Error status
        for ($i=0; $i<=10; $i++) {
            $messages = $messageManagement->receive(Queue::EXPORT(), 10);
            $this->assertEquals(1, count($messages));
            $this->assertEquals($i, $messages[0]->getData('number_of_trials'));
            $messageManagement->reject([$messages[0]->getId()], 'retry');
        }

        $messagesStep2 = $messageManagement->receive(Queue::EXPORT(), 10);
        $this->assertEquals(0, count($messagesStep2));
    }
}
