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

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchResults;
use MageOS\NetSuiteConnector\Core\Api\Data\MonitorItemInterface;
use MageOS\NetSuiteConnector\Core\Api\MonitorRegistryInterface;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process;

class MonitorRepository implements MonitorRegistryInterface
{
    /**
     * @var MonitorItemInterface[]
     */
    private array $itemCache = [];
    private \MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Monitor\MonitorItem\CollectionFactory $itemCollectionFactory;
    private \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor;
    private \Magento\Framework\Api\SearchResultsFactory $searchResultsFactory;
    private \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory;
    private \MageOS\NetSuiteConnector\Core\Api\Data\MonitorItemInterfaceFactory $monitorItemFactory;
    private \MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Monitor\MonitorItem $monitorItemResource;

    public function __construct(
        \Magento\Framework\Api\SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        \Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface $collectionProcessor,
        \Magento\Framework\Api\SearchResultsFactory $searchResultsFactory,
        \MageOS\NetSuiteConnector\Core\Api\Data\MonitorItemInterfaceFactory $monitorItemFactory,
        \MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Monitor\MonitorItem $monitorItemResource,
        \MageOS\NetSuiteConnector\Queue\Model\ResourceModel\Monitor\MonitorItem\CollectionFactory $itemCollectionFactory
    ) {

        $this->itemCollectionFactory = $itemCollectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->searchCriteriaBuilderFactory = $searchCriteriaBuilderFactory;
        $this->monitorItemFactory = $monitorItemFactory;
        $this->monitorItemResource = $monitorItemResource;
    }

    public function create(): MonitorItemInterface
    {
        return $this->monitorItemFactory->create();
    }

    /**
     * @param MonitorItemInterface|\Magento\Framework\Model\AbstractModel $monitorItem
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function save(MonitorItemInterface $monitorItem): void
    {
        $this->monitorItemResource->save($monitorItem);
        $cacheKey = $this->getCacheKey($monitorItem);
        $this->itemCache[$cacheKey] = $monitorItem;
    }

    public function getById(int $monitorId): ?MonitorItemInterface
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter('monitor_id', $monitorId);

        $result = $this->getList($searchCriteriaBuilder->create());
        /** @var MonitorItemInterface[] $items */
        $items = $result->getItems();
        if (count($items) == 0) {
            return null;
        }

        $item = array_shift($items);
        $item->afterLoad();
        return $item;
    }

    public function getByMessageId(int $messageId): ?MonitorItemInterface
    {
        $cacheKey = 'message-' . $messageId;
        if (isset($this->itemCache[$cacheKey])) {
            return $this->itemCache[$cacheKey];
        }

        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter('message_id', $messageId);

        /** @var MonitorItemInterface[] $items */
        $items = $this->getList($searchCriteriaBuilder->create())->getItems();

        if (count($items) == 0) {
            return null;
        }

        $item = array_shift($items);
        $item->afterLoad();
        $this->refresh($item);
        return $item;
    }

    public function getListByIds(array $messageIds): array
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter('message_id', $messageIds, 'in');

        /** @var MonitorItemInterface[] $items */
        $items = $this->getList($searchCriteriaBuilder->create())->getItems();

        if (count($items) == 0) {
            return [];
        }

        foreach ($items as $item) {
            $item->afterLoad();
            $this->refresh($item);
        }

        return $items;
    }

    public function getList(SearchCriteria $searchCriteria): SearchResults
    {
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);

        $collection = $this->itemCollectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    public function delete(MonitorItemInterface $monitorItem): void
    {
        $cacheKey = $this->getCacheKey($monitorItem);
        $this->monitorItemResource->delete($monitorItem);
        unset($this->itemCache[$cacheKey]);
    }

    public function refresh(MonitorItemInterface $monitorItem): void
    {
        $cacheKey = $this->getCacheKey($monitorItem);
        $this->itemCache[$cacheKey] = $monitorItem;
    }

    private function getCacheKey(MonitorItemInterface $monitorItem)
    {
        return 'message-' . $monitorItem->getMessageId();
    }
}
