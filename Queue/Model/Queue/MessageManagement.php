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

namespace MageOS\NetSuiteConnector\Queue\Model\Queue;

use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Enum\Message\Status;
use MageOS\NetSuiteConnector\Core\Exception\ConnectorRuntimeException;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\FromJSON;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\RecordPriority;
use MageOS\NetSuiteConnector\Queue\Model\Queue\Handlers\PostProcessHandlerInterface;

class MessageManagement implements MessageManagementInterface
{
    public function __construct(
        private readonly \Magento\Framework\ObjectManager\ConfigInterface $config,
        private readonly \Magento\Framework\Serialize\Serializer\Json $serializer,
        private readonly \MageOS\NetSuiteConnector\Core\Model\Config\QueueConfig $queueConfig,
        private readonly \MageOS\NetSuiteConnector\Queue\Model\Queue\MessageFactory $messageFactory,
        private readonly \MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Queue\Message $messageResource,
        private readonly \MageOS\NetSuiteConnector\Core\Api\MonitorManagementInterface $monitorManagement,
        private readonly \MageOS\NetSuiteConnector\Queue\Model\Queue\Handlers\PostProcessAggregator $postProcessAggregator,
        private array $actions = [],
        protected array $messages = []
    ) {
    }

    public function changeStatus(array $messageIds, Status $status, ?string $reason = null): void
    {
        $this->messageResource->changeStatus($messageIds, (string)$status);
        $this->postProcessAggregator->runPostProcess($messageIds, $status);

        if ($status->equals(Status::DONE())) {
            foreach ($messageIds as $messageId) {
                $this->monitorManagement->markMessageCompleted((int)$messageId, $reason);
            }
        }
        if ($status->equals(Status::ERROR())) {
            foreach ($messageIds as $messageId) {
                $this->monitorManagement->markMessageError((int)$messageId, $reason);
            }
        }

        if ($status->equals(Status::RETRY()) || $status->equals(Status::IN_QUEUE())) {
            foreach ($messageIds as $messageId) {
                $this->monitorManagement->markMessageRetry((int)$messageId, $reason);
            }
        }
    }

    public function reject(array $messageIds, ?string $reason = null, bool $forceReject = false): void
    {
        /**
         * TODO: Add logic when this should be ERROR.
         * 1. When number_of_trials > QueueConfig::reject_counter => RETRY
         * 2. If error message contains something from NetSuite (Header not received,...) => RETRY
         *
         * Move this into separate method/class?
         */
        $errorMessageIds = [];
        $retryMessageIds = [];
        if (!$forceReject) {
            foreach ($messageIds as $messageId) {
                $message = $this->messages[$messageId] ?? $this->getMessageById($messageId);

                if (RecordPriority::getPriority($message->getObject()) == 0
                    && $message->getData('number_of_trials') >= 10
                ) {
                    $errorMessageIds[] = $messageId;
                    continue;
                }

                $retryMessageIds[] = $messageId;
            }
        } else {
            $errorMessageIds = $messageIds;
        }

        if (!empty($errorMessageIds)) {
            $this->changeStatus(
                $errorMessageIds,
                Status::ERROR(),
                'Retried 10 times or force rejecting, changing status to Error. Last error: ' . $reason
            );
        }

        if (!empty($retryMessageIds)) {
            $this->changeStatus($retryMessageIds, Status::RETRY(), $reason);
        }
    }

    public function receive(Queue $queue, int $maxMessages): array
    {
        $messagesData = $this->messageResource->getMessages((string)$queue, $maxMessages);
        $messagesIds = array_column($messagesData, 'message_id');
        $marked = $this->messageResource->markMessagesInProgress($messagesIds);

        $messages = [];
        $messageIds = [];
        foreach ($messagesData as $messageData) {
            if (!in_array($messageData['message_id'], $marked)) {
                continue;
            }
            $messageIds[] = $messageData['message_id'];
            $message = $this->messageFactory->create(['data' => $messageData]);
            $this->prepareObject($message);
            $this->messages[$messageData['message_id']] = $message;

            $messages[] = $message;
        }

        $this->monitorManagement->markMessagesInProgress($messageIds);

        return $messages;
    }

    public function getMessageById(int $messageId): MessageInterface
    {
        $data = $this->messageResource->getMessage(['message_id' => $messageId]);
        if ($data === null || !is_array($data) || empty($data)) {
            throw new DataIntegrityException('Specified message id not found');
        }

        $marked = $this->messageResource->markMessagesInProgress([$messageId]);
        if (empty($marked)) {
            throw new DataIntegrityException('Specified message id is already being processed!');
        }

        $message = $this->messageFactory->create(['data' => $data]);
        $this->prepareObject($message);
        $this->messages[$messageId] = $message;

        return $message;
    }

    private function prepareObject(MessageInterface $message): void
    {
        if ($message->getObject()) {
            $object = null;
            $data = $this->serializer->unserialize($message->getObject());
            if (!empty($data['type'])) {
                $object = new $data['type']();
            }
            FromJSON::transform($object, $data['object']);
            $message->setData('body', $object);
        }
    }

    public function getStuckMessages(): array
    {
        $interval = $this->queueConfig->getDeleteExistingItemsInImportQueueHours();
        return $this->messageResource->getStuckMessages($interval);
    }

    public function deleteById(int $messageId): void
    {
        $this->messageResource->deleteMessages([$messageId]);
    }

    public function deleteSpecificMessage(int $messageId): void
    {
        $this->deleteById($messageId);
    }

    public function createMessage(
        string $action,
        int $itemId,
        Queue $queue,
        $record = null
    ): MessageInterface {
        $this->validateAction($queue, $action);

        return $this->messageFactory->create(
            [
                'data' => [
                    'action' => $action,
                    'item_id' => $itemId,
                    'queue' => (string)$queue,
                    'body' => $record,
                    'status' => (string)Status::IN_QUEUE()
                ]
            ]
        );
    }

    public function send(MessageInterface $message): int
    {
        if ($this->queueConfig->getDeleteExistingItemsInImportQueue()) {
            $conditions = [
                'action' => $message->getAction(),
                'item_id' => $message->getItemId(),
                'queue' => (string)$message->getQueue(),
            ];
            $originalMessages = $this->messageResource->getMessagesByConditions($conditions);
            if ($originalMessages !== null) {
                foreach ($originalMessages as $originalMessage) {
                    $this->processPossibleDuplicate($originalMessage);
                }
            }
            unset($originalMessages);
        }

        $message->setData(
            'priority',
            RecordPriority::getPriority($message->getObject())
        );

        $originalObject = $message->getObject();
        if ($message->getObject()) {
            $wrapper = [];
            $wrapper['type'] = get_class($message->getObject());
            $wrapper['object'] = $message->getObject();

            $body = $this->serializer->serialize($wrapper);
            $message->setData('body', $body);
        }

        $messageId = $this->messageResource->saveMessage($message->toArray());

        $message->setData('body', $originalObject);
        $this->monitorManagement->addToQueue(
            Process::translateFromQueue($message->getQueue()),
            $message,
            $messageId
        );

        return $messageId;
    }

    private function validateAction(Queue $queue, string $action): bool
    {
        $queue = str_replace('netsuite_', '', (string)$queue);
        if (!isset($this->actions[$queue])) {
            $class = 'MageOS\NetSuiteConnector\Core\Model\Process\\' . ucfirst($queue) . 'Processor';
            $arguments = $this->config->getArguments($class);
            if ($arguments === null) {
                throw new ConnectorRuntimeException('Class ' . $class . ' returned no arguments!');
            }
            if (!isset($arguments['processors']) || !is_array($arguments['processors'])) {
                throw new ConnectorRuntimeException('Class ' . $class . ' has no processors configured!');
            }
            //we have another structure after set:di:com done
            if (isset($arguments['processors']['_vac_']) && is_array($arguments['processors']['_vac_'])) {
                $this->actions[$queue] = array_keys($arguments['processors']['_vac_']);
            } else {
                $this->actions[$queue] = array_keys($arguments['processors']);
            }
        }

        if (!in_array($action, $this->actions[$queue])) {
            $errorMessage = sprintf(
                'Incorrect action passed: %s | Allowed actions: %s',
                $action,
                implode(',', $this->actions[$queue])
            );

            throw new ConnectorRuntimeException($errorMessage);
        }

        return true;
    }

    /**
     * @param array $messageLoad
     * @param string $queueName
     * @throws \MageOS\NetSuiteConnector\Core\Exception\ConnectorRuntimeException
     * @throws \Exception
     *
     * TODO: Figure our where \Exception is used and refactor it
     */
    public function addMessageToQueue(array $messageLoad, string $queueName = ''): void
    {
        //$messageLoad = 0 => $action, 1 => $id, 2 => $queueName, 3 => $serializableObject = null
        $message = $this->createMessage(
            $messageLoad[0],
            $messageLoad[1],
            new Queue($messageLoad[2]),
            $messageLoad[3] ?? null
        );

        $this->send($message);
    }

    public function transformToIds(array $messages): array
    {
        $messageIds = [];
        /** @var MessageInterface $message */
        foreach ($messages as $message) {
            if ($message->getId() !== null) {
                $messageIds[] = $message->getId();
            }
        }

        return $messageIds;
    }

    private function processPossibleDuplicate(array $message) : void
    {
        $doUpdate = false;
        $status = (string)$message['status'];
        if ($status === (string)Status::IN_PROGRESS()) {
            $now = new \DateTime();
            $pickedAt = \DateTime::createFromFormat(
                \Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT,
                $message['picked_at']
            );
            $hours = $this->queueConfig->getDeleteExistingItemsInImportQueueHours();
            $pickedAt->add(\DateInterval::createFromDateString($hours . ' hour'));
            if ($now > $pickedAt) {
                $doUpdate = true;
            }
        } else if (in_array($status, [(string)Status::IN_QUEUE(), (string)Status::RETRY()])) {
            $doUpdate = true;
        }
        if ($doUpdate) {
            $this->monitorManagement->markMessageCompleted((int)$message['message_id'],
                'Updated item received, removing this from queue.');
            $this->messageResource->deleteMessages([(int)$message['message_id']]);
        }
    }
}
