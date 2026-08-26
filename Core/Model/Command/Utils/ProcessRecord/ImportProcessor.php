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

namespace MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord;

use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;

class ImportProcessor implements RecordProcessorInterface
{
    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement;
    private \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger;
    private \MageOS\NetSuiteConnector\Core\Model\Process\Import\ImportProcessorInterface $importProcessor;
    private RequestBuilderInterface $requestBuilder;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord\RequestBuilderInterface $requestBuilder,
        \MageOS\NetSuiteConnector\Core\Model\Process\Import\ImportProcessorInterface $importProcessor
    ) {
        $this->serviceManagement = $serviceManagement;
        $this->logger = $logger;
        $this->requestBuilder = $requestBuilder;
        $this->importProcessor = $importProcessor;
    }

    /**
     * @throws NetSuiteRuntimeException
     * @throws DataIntegrityException
     */
    public function execute(array $recordIds): void
    {
        $request = $this->requestBuilder->getNetsuiteRequest($recordIds);
        $service = $this->serviceManagement->get();
        $response = $service->search($request);
        ResponseValidator::validate($response);

        $records = $response->searchResult->recordList->record;
        if ($records === null) {
            $this->logger->error('No records found with requested Ids');
            return;
        }
        foreach ($records as $record) {
            if (!$record) {
                $this->logger->error('Returned result contains empty row.');
                continue;
            }
            if (!$this->importProcessor->isMagentoImportable($record)) {
                $this->logger->error(sprintf(
                    'Record is not MagentoImportable, skipping. Record ID: %s',
                    $record->internalId
                ));
                continue;
            }

            $this->importProcessor->process($record);
        }
    }
}
