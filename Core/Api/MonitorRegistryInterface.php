<?php

namespace MageOS\NetSuiteConnector\Core\Api;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchResults;
use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Api\Data\MonitorItemInterface;
use MageOS\NetSuiteConnector\Core\Model\Monitor\Data\Process;

interface MonitorRegistryInterface
{
    public function create(): MonitorItemInterface;

    public function save(MonitorItemInterface $monitorItem): void;

    public function getById(int $monitorId): ?MonitorItemInterface;

    public function getByMessageId(int $messageId): ?MonitorItemInterface;

    public function getListByIds(array $messageIds): ?array;

    public function getList(SearchCriteria $searchCriteria): SearchResults;

    public function delete(MonitorItemInterface  $monitorItem): void;

    public function refresh(MonitorItemInterface  $monitorItem): void;
}
