<?php

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite\Service;

use Magento\Framework\App\ObjectManager;
use NetSuite\Classes\SearchMoreWithIdRequest;
use NetSuite\Classes\SearchRequest;

class Search
{
    private const CACHE_PATH = 'var/cache/netsuite/';

    /**
     * @var \NetSuite\Classes\SearchRequest
     */
    private $searchRequest;

    /** @var string|null */
    private $cacheKey = null;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management
     */
    private $serviceManagement;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Logger\Logger
     */
    private $logger;

    /**
     * NetSuiteSearch constructor.
     * @param SearchRequest $searchRequest
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement
     * @param \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger
     */
    public function __construct(
        \NetSuite\Classes\SearchRequest $searchRequest,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        ?\MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger = null
    ) {
        $this->searchRequest = $searchRequest;
        $this->serviceManagement = $serviceManagement;
        $this->logger = $logger ?: ObjectManager::getInstance()
            ->get(\MageOS\NetSuiteConnector\Core\Model\Logger\Logger::class);
    }

    /**
     * @param \NetSuite\Classes\SearchRequest|\NetSuite\Classes\SearchMoreWithIdRequest $searchRequest
     * @return \NetSuite\Classes\SearchMoreWithIdResponse|\NetSuite\Classes\SearchResponse
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException
     */
    private function queryNS($searchRequest)
    {
        $cache = $this->getCache($searchRequest);
        if ($cache) {
            return $cache;
        }

        $netsuiteService = $this->serviceManagement->get();

        if ($searchRequest instanceof SearchMoreWithIdRequest) {
            $requestMethod = function () use ($searchRequest, $netsuiteService) {
                return $netsuiteService->searchMoreWithId($searchRequest);
            };
        } else {
            $requestMethod = function () use ($searchRequest, $netsuiteService) {
                return $netsuiteService->search($searchRequest);
            };
        }

        $response = $this->serviceManagement->retryNetSuiteQuery($requestMethod);
        $this->setCache($searchRequest, $response);

        return $response;
    }

    private function setCache($searchRequest, $response)
    {
        $cachePath = $this->getCachePath($searchRequest);
        if (!$cachePath) {
            return null;
        }

        try {
            if (!is_dir(self::CACHE_PATH) && !@mkdir(self::CACHE_PATH)) {// phpcs:ignore
                throw new \RuntimeException("Can't create cache dir:" . self::CACHE_PATH);
            }

            file_put_contents($cachePath, json_encode($response));// phpcs:ignore
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage());
        }
    }

    private function getCache($searchRequest)
    {
        $cachePath = $this->getCachePath($searchRequest);
        if ($cachePath && file_exists($cachePath)) {// phpcs:ignore
            return json_decode(file_get_contents($cachePath));// phpcs:ignore
        }

        return null;
    }

    private function getCachePath($searchRequest): ?string
    {
        $cachePath = null;

        if ($this->cacheKey) {
            $pageKey = $searchRequest instanceof SearchMoreWithIdRequest ? $searchRequest->pageIndex : '0';
            $cachePath = self::CACHE_PATH . $this->cacheKey . '_' . $pageKey;
        }

        return $cachePath;
    }

    /**
     * @param int $resumeAt
     * @return \Generator
     */
    public function all(int $resumeAt = 0)
    {
        foreach ($this->allInBatches($resumeAt) as $batch) {
            if (!empty($batch)) {
                foreach ($batch as $record) {
                    yield $record;
                }
            }
        }
    }

    /**
     * @param int $resumeAt
     * @return \Generator
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException
     */
    public function allInBatches(int $resumeAt = 0)
    {
        $response = $this->queryNS($this->searchRequest);
        if (null === $resumeAt || $resumeAt <= 1) {
            $this->logger->addInfo('Page 1');
            if ($response->searchResult->recordList) {
                yield $response->searchResult->recordList->record;
            } else {
                yield $response->searchResult->searchRowList->searchRow;
            }
        }

        $totalPages = $response->searchResult->totalPages;
        $searchId = $response->searchResult->searchId;

        $this->logger->addInfo("Processing $totalPages pages");

        $start = max(2, $resumeAt);
        for ($i = $start; $i <= $totalPages; $i++) {
            $this->logger->addInfo("Page $i of $totalPages");

            $searchMoreRequest = new SearchMoreWithIdRequest();
            $searchMoreRequest->pageIndex = $i;
            $searchMoreRequest->searchId = $searchId;

            $response = $this->queryNS($searchMoreRequest);

            if ($response->searchResult->recordList) {
                yield $response->searchResult->recordList->record;
            } else {
                yield $response->searchResult->searchRowList->searchRow;
            }
        }
    }

    /**
     * To enable search result caching (useful for development) set cache key to something
     * @param null|string $cacheKey
     */
    public function setCacheKey($cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }
}
