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

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite\Service;

use NetSuite\Classes\GetListRequest;
use NetSuite\Classes\GetRequest;
use NetSuite\Classes\ItemSearchBasic;
use NetSuite\Classes\ItemType;
use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SearchEnumMultiSelectField;
use NetSuite\Classes\SearchEnumMultiSelectFieldOperator;
use NetSuite\Classes\SearchMultiSelectField;
use NetSuite\Classes\SearchMultiSelectFieldOperator;
use NetSuite\Classes\SearchRequest;
use MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Search;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management;

/**
 * This is the Repository class for actual \NetSuite\NetSuiteService request
 * Because there are bunch of \NetSuite\Classes\* references, phpMd is complaining about coupling between objects.
 * Ignoring it.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Repository
{
    /**
     * @var array
     */
    protected $cachedLists = [];
    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    private $cache;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Config\CacheConfig
     */
    private $cacheConfig;
    /**
     * @var Management
     */
    private $serviceManagement;

    public function __construct(
        \Magento\Framework\App\CacheInterface $cache,
        \MageOS\NetSuiteConnector\Core\Model\Config\CacheConfig $cacheConfig,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement
    ) {
        $this->cache = $cache;
        $this->cacheConfig = $cacheConfig;
        $this->serviceManagement = $serviceManagement;
    }

    public function fetchRecordFromNetSuite(string $recordType, int $id)
    {
        $request = new GetRequest();
        $request->baseRef = new RecordRef();
        $request->baseRef->internalId = $id;
        $request->baseRef->type = $recordType;

        $getResponse = $this->serviceManagement->get()->get($request);
        ResponseValidator::validate($getResponse);

        return $getResponse->readResponse->record;
    }

    /**
     * TODO: Change into Generator using "yield"
     *
     * @param string $recordType
     * @param array $ids
     * @return array
     * @throws NetSuiteRuntimeException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     */
    public function fetchMultipleRecordsFromNetSuite(string $recordType, array $ids)
    {
        $ids = array_filter($ids, 'trim');
        if (count($ids) == 0) {
            return [];
        }

        $batches = array_chunk($ids, 50);
        $result = [];

        foreach ($batches as $batch) {
            // phpcs:ignore
            $result = array_merge($result, $this->getList($recordType, $batch));
        }

        return $result;
    }

    /**
     * @param string $recordType
     * @param array $ids
     * @return array
     * @throws NetSuiteRuntimeException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     */
    private function getList(string $recordType, array $ids)
    {
        $request = new GetListRequest();
        $request->baseRef = [];

        foreach ($ids as $id) {
            $baseRef = new RecordRef();
            $baseRef->internalId = $id;
            $baseRef->type = $recordType;
            $request->baseRef[] = $baseRef;
        }

        /** @var \NetSuite\Classes\GetListResponse $getResponse */
        $getResponse = $this->serviceManagement->retryNetSuiteQuery(function () use ($request) {
            return $this->serviceManagement->get()->getList($request);
        });

        $result = [];
        foreach ($getResponse->readResponseList->readResponse as $response) {
            $result[] = $response->record;
        }

        return $result;
    }

    /**
     * Less efficient than above method, but doesn't require record type
     * @param array $ids
     * @return \Generator
     */
    public function fetchItemsFromNetSuite(array $ids)
    {
        if (empty($ids)) {
            return [];
        }

        $typeField = new SearchEnumMultiSelectField();
        $typeField->operator = SearchEnumMultiSelectFieldOperator::anyOf;
        $typeField->searchValue[] = ItemType::_kit;
        $typeField->searchValue[] = ItemType::_inventoryItem;
        $typeField->searchValue[] = ItemType::_nonInventoryItem;
        $typeField->searchValue[] = ItemType::_assembly;

        $idField = new SearchMultiSelectField();
        $idField->operator = SearchMultiSelectFieldOperator::anyOf;

        foreach ($ids as $id) {
            $rref = new RecordRef();
            $rref->internalId = $id;
            $idField->searchValue[] = $rref;
        }

        $tranSearchBasic = new ItemSearchBasic();
        $tranSearchBasic->type = $typeField;
        $tranSearchBasic->internalId = $idField;

        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $tranSearchBasic;

        $searcher = new Search(
            $searchRequest,
            $this->serviceManagement
        );

        yield from $searcher->all();
    }

    public function getListValue($listInternalId, $listItemInternalId)
    {
        $cacheKey = 'custom_list_' . $listInternalId;

        if (!$listInternalId) {
            return null;
        }

        if (isset($this->cachedLists[$listInternalId])) {
            return $this->cachedLists[$listInternalId][$listItemInternalId];
        }

        $cachedList = $this->loadFromCache($cacheKey);
        if (!empty($cachedList)) {
            $this->cachedLists[$listInternalId] = $cachedList;

            if (isset($this->cachedLists[$listInternalId][$listItemInternalId])) {
                return $this->cachedLists[$listInternalId][$listItemInternalId];
            }
        }

        $getListRequest = new GetListRequest();
        $getListRequest->baseRef = new RecordRef();
        $getListRequest->baseRef->internalId = $listInternalId;
        $getListRequest->baseRef->type = RecordType::customList;

        $response = $this->serviceManagement->retryNetSuiteQuery(function () use ($getListRequest) {
            return $this->serviceManagement->get()->getList($getListRequest);
        });

        foreach ($response->readResponseList->readResponse[0]->record->customValueList->customValue as $listValue) {
            $this->cachedLists[$listInternalId][$listValue->valueId] = $listValue->value;
        }

        $this->saveInCache($this->cachedLists[$listInternalId], $cacheKey);

        return $this->cachedLists[$listInternalId][$listItemInternalId];
    }

    /**
     * Wrapper for the ServiceManagement::getServerTime to allow smaller Class needs
     * This way, we don't need to set Preference to a million classes but only to this one
     * to get ServiceManagement as a Proxy (which makes loading of DIs faster!)
     *
     * @return string
     * @throws NetSuiteRuntimeException
     */
    public function getServerTime(): string
    {
        return $this->serviceManagement->getServerTime();
    }

    /**
     * @param $id
     * @return array
     */
    protected function loadFromCache($id): array
    {
        $value = $this->cache->load($id);
        return $value ? json_decode($value, true) : [];
    }

    /**
     * @param $data
     * @param $key
     */
    protected function saveInCache($data, $key): void
    {
        $data = json_encode($data);
        $this->cache->save(
            $data,
            $key,
            [\Magento\Framework\App\Cache\Type\Config::CACHE_TAG],
            $this->getCacheLifetime()
        );
    }

    /**
     * @return int
     */
    protected function getCacheLifetime(): int
    {
        return (int)$this->cacheConfig->getCacheSecondsForListsAndCustomRecords();
    }
}
