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
use MageOS\NetSuiteConnector\Core\Api\Data\MonitorItemInterface;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\ProcessOutput;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Status;

class MonitorItem extends \Magento\Framework\Model\AbstractModel implements MonitorItemInterface
{
    private const MESSAGE_ID = 'message_id';
    private const PROCESS = 'process';
    private const ENTITY = 'entity';
    private const ITEM_ID = 'item_id';
    private const CREATED_AT = 'created_at';
    private const PROCESSED_AT = 'processed_at';
    private const STATUS = 'status';
    private const COUNTER = 'counter';
    private const HAS_PAYLOAD = 'has_payload';
    private const PAYLOAD = 'payload';
    private const PAYLOAD_DECODED = 'payload_decoded';
    private const PAYLOAD_INSTANCE = 'payload_instance';
    private const OVERWRITE_PAYLOAD = 'overwrite_payload';
    private const PROCESS_OUTPUT = 'process_output';
    private const PROCESS_OUTPUT_DECODED = 'process_output_decoded';

    protected $_resourceName = \MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Monitor\MonitorItem::class;
    private \Magento\Framework\Serialize\Serializer\Json $serializer;

    public function __construct(
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->serializer = $serializer;
    }

    public function setMessageId(int $id): void
    {
        $this->setData(self::MESSAGE_ID, $id);
    }

    public function getMessageId(): int
    {
        return (int)$this->getData(self::MESSAGE_ID);
    }

    public function setProcess(Process $type): void
    {
        $this->setData(self::PROCESS, (string)$type);
    }

    public function getProcess(): Process
    {
        return new Process($this->getData(self::PROCESS));
    }

    public function setEntity(string $entity): void
    {
        $this->setData(self::ENTITY, $entity);
    }

    public function getEntity(): string
    {
        return $this->getData(self::ENTITY);
    }

    public function setItemId(int $itemId): void
    {
        $this->setData(self::ITEM_ID, $itemId);
    }

    public function getItemId(): int
    {
        return (int)$this->getData(self::ITEM_ID);
    }

    public function setCreatedAt(string $date): void
    {
        $this->setData(self::CREATED_AT, $date);
    }

    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    public function setProcessedAt(string $date): void
    {
        $this->setData(self::PROCESSED_AT);
    }

    public function getProcessedAt(): string
    {
        return (string)$this->getData(self::PROCESSED_AT);
    }

    public function setStatus(Status $status): void
    {
        $this->setData(self::STATUS, (string)$status);
    }

    public function getStatus(): Status
    {
        return new Status($this->getData(self::STATUS));
    }

    public function hasStatus(Status $status): bool
    {
        return ((string)$status) == $this->getData(self::STATUS);
    }

    public function triggerCounter(): void
    {
        $counter = $this->getData(self::COUNTER) ?? 0;
        $counter++;
        $this->setData(self::COUNTER, $counter);
    }

    public function getCounter(): int
    {
        return (int)$this->getData(self::COUNTER);
    }

    public function setHasPayload(bool $hasPayload): void
    {
        $this->setData(self::HAS_PAYLOAD, $hasPayload);
    }

    public function getHasPayload(): bool
    {
        return (bool)$this->getData(self::HAS_PAYLOAD);
    }

    public function setPayload(Record $payload): void
    {
        $this->setData(self::PAYLOAD, $payload);
        try {
            $decoded = $this->serializer->unserialize(
                $this->serializer->serialize($payload)
            );
        } catch (\InvalidArgumentException $e) {
            $decoded = [];
        }
        $this->setData(self::PAYLOAD_DECODED, $decoded);
    }

    public function setPayloadString(string $payload): void
    {
        try {
            $decoded = $this->serializer->unserialize($payload);
            // We re-serialize the payload so we clean up prettify output
            $this->setData(self::PAYLOAD, $this->serializer->serialize($decoded));
        } catch (\InvalidArgumentException $e) {
            $decoded = [];
        }
        $this->setData(self::PAYLOAD_DECODED, $decoded);
    }

    public function getPayload(): array
    {
        return (array)$this->getData(self::PAYLOAD_DECODED);
    }

    public function setPayloadInstance(string $instance): void
    {
        $this->setData(self::PAYLOAD_INSTANCE, $instance);
    }

    public function getPayloadInstance(): string
    {
        return $this->getData(self::PAYLOAD_INSTANCE);
    }

    public function setOverwritePayload(bool $overwrite): void
    {
        $this->setData(self::OVERWRITE_PAYLOAD, $overwrite);
    }

    public function getOverwritePayload(): bool
    {
        return (bool)$this->getData(self::OVERWRITE_PAYLOAD);
    }

    public function setProcessOutput(array $output): void
    {
        $this->setData(self::PROCESS_OUTPUT_DECODED, $output);
    }

    public function getProcessOutput(): array
    {
        return (array)$this->getData(self::PROCESS_OUTPUT_DECODED);
    }

    public function getProcessOutputEncoded(): string
    {
        return (string)$this->getData(self::PROCESS_OUTPUT);
    }

    public function addProcessOutput(ProcessOutput $output): void
    {
        $processOutput = (array)$this->getProcessOutput();
        $processOutput[] = $output->__toArray();

        $this->setProcessOutput($processOutput);
    }

    public function afterLoad()
    {
        try {
            $decoded = $this->serializer->unserialize($this->getData(self::PROCESS_OUTPUT));
        } catch (\InvalidArgumentException $e) {
            $decoded = [];
        }
        $this->setData(self::PROCESS_OUTPUT_DECODED, $decoded);

        try {
            $decoded = $this->serializer->unserialize($this->getData(self::PAYLOAD));
        } catch (\InvalidArgumentException $e) {
            $decoded = [];
        }
        $this->setData(self::PAYLOAD_DECODED, $decoded);

        return parent::afterLoad();
    }

    public function beforeSave()
    {
        try {
            $encoded = $this->serializer->serialize($this->getData(self::PROCESS_OUTPUT_DECODED));
        } catch (\InvalidArgumentException $e) {
            $encoded = [];
        }
        $this->setData(self::PROCESS_OUTPUT, $encoded);

        try {
            if ($this->getData(self::PAYLOAD) instanceof Record) {
                $encoded = $this->serializer->serialize($this->getData(self::PAYLOAD));
                $this->setData(self::PAYLOAD, $encoded);
            }
        } catch (\InvalidArgumentException $e) {// phpcs:ignore

        }

        return parent::beforeSave();
    }
}
