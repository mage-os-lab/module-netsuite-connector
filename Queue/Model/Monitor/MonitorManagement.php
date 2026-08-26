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
 *
 */

namespace MageOS\NetSuiteConnector\Queue\Model\Monitor;

use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Api\Data\MonitorItemInterface;
use MageOS\NetSuiteConnector\Core\Api\MonitorManagementInterface;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\OutputLevel;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\ProcessOutput;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Status;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\FromJSON;

class MonitorManagement implements MonitorManagementInterface
{
    private \MageOS\NetSuiteConnector\Queue\Model\Monitor\MonitorRepository $monitorRepository;
    private \MageOS\NetSuiteConnector\Core\Model\Config\MonitorConfig $monitorConfig;
    private \MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Monitor\FastMonitorItem $fastMonitorResource;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Config\MonitorConfig $monitorConfig,
        \MageOS\NetSuiteConnector\Queue\Model\Monitor\MonitorRepository $monitorRepository,
        \MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Monitor\FastMonitorItem $fastMonitorResource
    ) {

        $this->monitorRepository = $monitorRepository;
        $this->monitorConfig = $monitorConfig;
        $this->fastMonitorResource = $fastMonitorResource;
    }

    public function addToQueue(Process $process, MessageInterface $message, int $messageId): void
    {
        $entity = $message->getAction();
        $entityId = $message->getItemId();

        $monitorItem = $this->monitorRepository->create();
        $monitorItem->setStatus(Status::IN_QUEUE());
        $monitorItem->setProcess(Process::translateFromQueue($message->getQueue()));
        $monitorItem->setEntity($entity);
        $monitorItem->setItemId($entityId);
        $monitorItem->addProcessOutput(new ProcessOutput('Added to the Queue'));
        $monitorItem->setMessageId($messageId);
        $monitorItem->setHasPayload(false);

        if ($message->getObject() !== null) {
            $monitorItem->setHasPayload(true);
            $monitorItem->setPayloadInstance(get_class($message->getObject()));
            $monitorItem->setPayload($message->getObject());
        }

        $monitorItem->beforeSave();
        $monitorData = $monitorItem->toArray([
            'status', 'process', 'entity', 'item_id', 'process_output', 'message_id', 'has_payload',
            'payload_instance', 'payload'
        ]);

        $this->fastMonitorResource->saveMonitorItem($monitorData);
    }

    public function markMessagesInProgress(array $messageIds): void
    {
        $monitorItems = $this->monitorRepository->getListByIds($messageIds);
        foreach ($monitorItems as $monitorItem) {
            $monitorItem->addProcessOutput(new ProcessOutput(
                'Status change: In Progress',
                OutputLevel::DEBUG()
            ));
            $monitorItem->setStatus(Status::IN_PROGRESS());

            $this->saveMarkedMessage($monitorItem, true);
        }
    }

    public function markMessageCompleted(int $messageId, ?string $reason = null): void
    {
        /**
         * If remove_if_success we don't update the entry but remove it from the table
         */
        if ($this->monitorConfig->getRemoveIfSuccess()) {
            $monitorItem = $this->monitorRepository->getByMessageId($messageId);
            if ($monitorItem === null) {
                return;
            }

            $this->monitorRepository->delete($monitorItem);
            return;
        }

        $reason = $reason ?? 'Process completed successfully!';
        $monitorItem = $this->markMessage($messageId, Status::DONE(), $reason);
        if (!$monitorItem) {
            return;
        }

        $monitorItem->setProcessedAt((new \DateTime())->format('Y-m-d H:i:s'));
        $this->monitorRepository->refresh($monitorItem);
        $monitorItem->beforeSave();

        $this->fastMonitorResource->changeStatusWithData(
            $monitorItem->getMessageId(),
            [
                'status' => (string)Status::DONE(),
                'process_output' => $monitorItem->getProcessOutputEncoded(),
                'processed_at' => $monitorItem->getProcessedAt()
            ]
        );
    }

    public function markMessageError(int $messageId, string $error): void
    {
        $monitorItem = $this->markMessage($messageId, Status::ERROR(), $error);
        if ($monitorItem) {
            $this->monitorRepository->save($monitorItem);
        }
    }

    public function markMessageRetry(int $messageId, string $error): void
    {
        $monitorItem = $this->markMessage($messageId, Status::RETRY(), $error);
        if (!$monitorItem) {
            return;
        }

        $this->monitorRepository->save($monitorItem);
        $this->monitorRepository->refresh($monitorItem);

        $this->fastMonitorResource->changeStatusWithData(
            $monitorItem->getMessageId(),
            [
                'status' => (string)Status::RETRY(),
            ]
        );
    }

    private function saveMarkedMessage(MonitorItemInterface $monitorItem, bool $triggerCounter = false)
    {
        $this->monitorRepository->refresh($monitorItem);

        $monitorItem->beforeSave();
        $this->fastMonitorResource->changeStatus(
            $monitorItem->getMessageId(),
            (string)STATUS::IN_PROGRESS(),
            $monitorItem->getProcessOutputEncoded(),
            $triggerCounter
        );
    }

    public function getModifiedObject(MessageInterface $message): ?Record
    {
        $monitorItem = $this->monitorRepository->getByMessageId((int)$message->getId());
        if ($monitorItem === null) {
            return null;
        }

        if (!$monitorItem->getHasPayload() || !$monitorItem->getOverwritePayload()) {
            return null;
        }

        $monitorItem->addProcessOutput(new ProcessOutput(
            'Modified Data being used.',
            OutputLevel::DEBUG()
        ));
        $this->monitorRepository->save($monitorItem);

        $instance = $monitorItem->getPayloadInstance();
        $object = new $instance();
        FromJSON::transform($object, $monitorItem->getPayload());

        return $object;
    }

    public function setModifiedObject(MessageInterface $message, Record $record): void
    {
        $monitorItem = $this->monitorRepository->getByMessageId((int)$message->getId());
        if ($monitorItem === null) {
            return;
        }

        $monitorItem->setPayload($record);
        $monitorItem->setHasPayload(true);
        $monitorItem->setPayloadInstance(get_class($record));

        $monitorItem->addProcessOutput(new ProcessOutput(
            'Payload is set',
            OutputLevel::DEBUG()
        ));
        $this->monitorRepository->save($monitorItem);
    }

    private function markMessage(int $messageId, Status $status, string $output): ?MonitorItemInterface
    {
        $monitorItem = $this->monitorRepository->getByMessageId($messageId);
        if ($monitorItem === null) {
            return null;
        }

        $monitorItem->setStatus($status);
        $monitorItem->addProcessOutput(new ProcessOutput($output));
        $monitorItem->addProcessOutput(new ProcessOutput(
            'Status change: ' . Status::getLabel($status),
            OutputLevel::DEBUG()
        ));

        return $monitorItem;
    }
}
