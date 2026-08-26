<?php declare(strict_types=1);
/**
 *  RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */
namespace MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord\DateRange;

use NetSuite\Classes\SearchMoreWithIdRequest;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord\RecordProcessorInterface;
use MageOS\NetSuiteConnector\Core\Enum\Record\DateRange;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;

class ImportProcessor implements RecordProcessorInterface
{
    public function __construct(
        private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        private \MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord\RequestBuilderInterface $requestBuilder,
        private \MageOS\NetSuiteConnector\Core\Model\Process\Import\ImportProcessorInterface $importProcessor,
        private \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        private \NetSuite\Classes\SearchResponse|\NetSuite\Classes\SearchMoreWithIdResponse|null $response = null
    ) {
    }

    /**
     * Two steps:
     *  - Get specific type records from NS
     *  - Call import of this entity
     *
     * @param array $recordIds
     * @return void
     */
    public function execute(array $recordIds): void
    {
        try {
            $currentPage = 1;
            while ($records = $this->getRecords($recordIds, $currentPage)) {
                foreach ($records as $record) {
                    try {
                        $this->importProcessor->process($record);
                    } catch (\Exception $e) {
                        $this->logger->error(
                            sprintf('Something went wrong during process a record. Details %s.', $e->getMessage())
                        );
                    }
                }

                $this->logger->info(sprintf('The page %s processed successfully', $currentPage));
                $currentPage++;
            }
        } catch (\Exception $e) {
            $this->logger->error(
                sprintf('Something went wrong during process records by date range. Details %s.', $e->getMessage())
            );
        }
    }

    /**
     * Send requests to NetSuite
     *
     * @param array $recordIds
     * @param int $pageId
     * @return array
     * @throws DataIntegrityException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException
     */
    private function getRecords(array $recordIds, int $pageId): array
    {
        $debugInfo = sprintf(
            'Start to process the %s page, the batch size is %s',
            $pageId,
            $recordIds[DateRange::BATCH_SIZE->value]
        );
        $this->logger->info($debugInfo);
        $netsuiteService = $this->serviceManagement->get();
        $netsuiteService->setSearchPreferences(false, $recordIds[DateRange::BATCH_SIZE->value]);
        $searchRequest = $this->requestBuilder->getNetsuiteRequest($recordIds);
        if ($this->response === null && $pageId === 1) {
            $this->response = $netsuiteService->search($searchRequest);
            ResponseValidator::validate($this->response);
        }

        $totalPages = $this->response->searchResult->totalPages;
        $searchId = $this->response->searchResult->searchId;
        if ($pageId > $totalPages) {
            return [];
        }

        if ($this->response !== null && $searchId && $pageId > 1) {
            $searchMoreRequest = new SearchMoreWithIdRequest();
            $searchMoreRequest->pageIndex = $pageId;
            $searchMoreRequest->searchId = $searchId;
            $this->response = $netsuiteService->searchMoreWithId($searchMoreRequest);
            ResponseValidator::validate($this->response);
        }

        $debugInfo = sprintf(
            'The search results. The page is %s, the total records are %s, the total pages are %s',
            $pageId,
            $this->response->searchResult->totalRecords,
            $totalPages
        );
        $this->logger->info($debugInfo);
        return $this->response->searchResult->recordList->record;
    }
}
