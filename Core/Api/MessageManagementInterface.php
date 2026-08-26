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

namespace MageOS\NetSuiteConnector\Core\Api;

use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Enum\Message\Status;

interface MessageManagementInterface
{
    public function getMessageById(int $messageId): MessageInterface;

    public function deleteById(int $messageId): void;

    public function createMessage(
        string $action,
        int $itemId,
        Queue $queue,
        $record = null
    ): MessageInterface;

    public function receive(Queue $queue, int $maxMessages): array;

    public function send(MessageInterface $message): int;

    public function reject(array $messageIds, ?string $reason = null): void;

    public function changeStatus(array $messageIds, Status $status, ?string $reason = null): void;

    public function addMessageToQueue(array $messageLoad, string $queueName = ''): void;

    public function transformToIds(array $messages): array;

    public function getStuckMessages(): array;

    /**
     * @deprecated
     */
    public function deleteSpecificMessage(int $messageId): void;
}
