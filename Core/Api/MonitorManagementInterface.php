<?php

namespace MageOS\NetSuiteConnector\Core\Api;

use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process;

interface MonitorManagementInterface
{
    public function addToQueue(Process $process, MessageInterface $message, int $messageId): void;

    public function markMessagesInProgress(array $messageIds): void;

    public function markMessageCompleted(int $messageId, ?string $reason = null): void;

    public function markMessageError(int $messageId, string $error): void;

    public function markMessageRetry(int $messageId, string $error): void;

    /**
     * Method that returns Payload (if overwrite_payload is true)
     * @param MessageInterface $message
     * @return Record|null
     */
    public function getModifiedObject(MessageInterface $message): ?Record;

    public function setModifiedObject(MessageInterface $message, Record $record): void;
}
