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

namespace MageOS\NetSuiteConnector\Core\Api\Data;

use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Status;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\ProcessOutput;

interface MonitorItemInterface
{
    public function setMessageId(int $id): void;
    public function getMessageId(): int;

    public function setProcess(Process $type): void;
    public function getProcess(): Process;

    public function setEntity(string $entity): void;
    public function getEntity(): string;

    public function setItemId(int $itemId): void;
    public function getItemId(): int;

    public function setCreatedAt(string $date): void;
    public function getCreatedAt(): string;

    public function setProcessedAt(string $date): void;
    public function getProcessedAt(): string;

    public function setStatus(Status $status): void;
    public function getStatus(): Status;
    public function hasStatus(Status $status): bool;

    public function triggerCounter(): void;
    public function getCounter(): int;

    public function setHasPayload(bool $hasPayload): void;
    public function getHasPayload(): bool;

    public function setPayload(Record $payload): void;
    public function setPayloadString(string $payload): void;
    public function getPayload(): array;

    public function setPayloadInstance(string $instance): void;
    public function getPayloadInstance(): string;

    public function setOverwritePayload(bool $overwrite): void;
    public function getOverwritePayload(): bool;

    /**
     * @param ProcessOutput[] $output
     */
    public function setProcessOutput(array $output): void;
    public function getProcessOutput(): array;
    public function getProcessOutputEncoded(): string;

    public function addProcessOutput(ProcessOutput $output): void;
}
