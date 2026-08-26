<?php

namespace MageOS\NetSuiteConnector\Product\Plugin;

use MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface;
use MageOS\NetSuiteConnector\Core\Model\Process;

class ImportQueuePrefetchDataPlugin
{
    /**
     * @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface
     */
    private $messageManagement;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Process\ImportProcessor
     */
    private $importProcessor;

    /**
     * ImportQueuePrefetchDataPlugin constructor.
     * @param \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement
     * @param Process\ImportProcessor $importProcessor
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement,
        \MageOS\NetSuiteConnector\Core\Model\Process\ImportProcessor $importProcessor
    ) {
        $this->messageManagement = $messageManagement;
        $this->importProcessor = $importProcessor;
    }

    /**
     * Hook for plugins
     *
     * @param array $messages
     */

    /**
     * Prefetch products before process batch data from the import queue
     *
     * @param Process $subject
     * @param mixed $result
     * @param array $messages
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterBeforeProcessImportBatchData(Process $subject, $result, $messages)
    {
        // prefetch bundle items if needed
        $inventoryItems = [];
        /** @var MessageInterface $message */
        foreach ($messages as $message) {
            if ($message->getAction() === 'inventoryitem') {
                $inventoryItems[] = $message->getObject();
            }
        }

        if (!empty($inventoryItems)) {
            /** @var \MageOS\NetSuiteConnector\Product\Model\Process\Import\Item $processModel */
            $processModel = $this->importProcessor->getEntityProcessor('inventoryitem');
            $processModel->prefetchProducts($inventoryItems);
        }
        return $result;
    }
}
