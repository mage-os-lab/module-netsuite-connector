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

namespace MageOS\NetSuiteConnector\Core\Model;

use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Model\Process\Import\AbstractImportProcessor;

class ProcessManagement
{
    private \MageOS\NetSuiteConnector\Core\Model\Config\QueueConfig $queueConfig;
    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Repository $serviceRepository;
    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement;
    private \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Config\QueueConfig $queueConfig,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Repository $serviceRepository,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement
    ) {
        $this->queueConfig = $queueConfig;
        $this->serviceRepository = $serviceRepository;
        $this->serviceManagement = $serviceManagement;
        $this->messageManagement = $messageManagement;
    }

    public function processRecords(AbstractImportProcessor $importableEntityModel, array $records) : void
    {
        if ($importableEntityModel->shouldExtraLoadRecordOnImport()) {
            $records = $this->extraLoadEntitiesFromNetSuite($importableEntityModel, $records);
        }
        foreach ($records as $record) {
            if (!$record) {
                continue;
            }
            $this->processRecord($importableEntityModel, $record);
        }
    }

    private function processRecord(AbstractImportProcessor $importableEntityModel, $record): void
    {
        $message = $this->messageManagement->createMessage(
            $importableEntityModel->getMessageType(),
            (int)$record->internalId,
            Queue::IMPORT(),
            $record
        );
        $this->messageManagement->send($message);
    }

    private function extraLoadEntitiesFromNetSuite(
        AbstractImportProcessor $importableEntityModel,
        array $records
    ) : array {
        $recordIdsForImport = [];
        foreach ($records as $record) {
            if ($importableEntityModel->isMagentoImportable($record) &&
                !$importableEntityModel->isAlreadyImported($record)) {

                $recordIdsForImport[] = $record->internalId;
            }
        }

        return $this->serviceRepository->fetchMultipleRecordsFromNetSuite(
            $importableEntityModel->getRecordType(),
            $recordIdsForImport
        );
    }
}
