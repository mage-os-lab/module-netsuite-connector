<?php

namespace MageOS\NetSuiteConnector\Core\Test\Integration\Helper;

use PHPUnit\Util\Exception;

/**
 * Class NetSuiteServiceFaker -
 * @SuppressWarnings(PHPMD)
 */
class NetSuiteServiceFaker
{
    /**
     * @var array
     */
    private $parameters;

    private $addRequest;

    private $initializeRequest;

    private $updateRequest;
    private $pathToResponses;

    private $counter = [];

    public function __construct($pathToResponses)
    {
        $this->pathToResponses = $pathToResponses;
    }

    private $getRequest;

    public function setParameters(array $parameters)
    {
        $this->parameters = $parameters;
        $this->addRequest = null;
        $this->updateRequest = null;
    }

    public function search(\NetSuite\Classes\SearchRequest $searchRequest)
    {
        $parameters = $this->parameters;
        $this->setCounter('search');
        if ($this->counter['search'] > 1 && isset($this->parameters[$this->counter['search']])) {
            $parameters = $this->parameters[$this->counter['search']];
        }

        $searchRecord = $searchRequest->searchRecord;

        $success = $parameters['search_success'];
        if ($success && isset($parameters['netsuite_internal_id'])) {
            $netsuiteId = $parameters['netsuite_internal_id'];
        }
        if (isset($parameters['is_person'])) {
            $isPerson = $parameters['is_person'];
        }

        if (isset($parameters['record'])) {
            $record = $parameters['record'];
        }

        $suffix = isset($parameters['suffix']) ? $parameters['suffix'] : '';
        $fileName = $this->getResponseFile($searchRecord, $suffix);
        return include $fileName;
    }

    public function searchMoreWithId(\NetSuite\Classes\SearchMoreWithIdRequest $searchMoreRequest)
    {
        $parameters = $this->parameters;
        if ($searchMoreRequest->pageIndex > $parameters['failedAfter']) {
            throw new Exception('Stop Import!');
        }
        $response = new \NetSuite\Classes\SearchResponse();
        $response->searchResult = new \NetSuite\Classes\SearchResult();
        $response->searchResult->totalPages = $parameters['totalPages'];
        $response->searchResult->searchId = $parameters['searchId'];
        $response->searchResult->status = new \NetSuite\Classes\Status();
        $response->searchResult->status->isSuccess = true;
        $response->searchResult->recordList = new \NetSuite\Classes\RecordList();
        $response->searchResult->recordList->record = new \NetSuite\Classes\Record();

        return $response;
    }

    public function add(\NetSuite\Classes\AddRequest $addRequest)
    {
        $this->setCounter('add');
        $this->addRequest = $addRequest;
        $record = $addRequest->record;

        $success = $this->parameters['add_success'];
        if ($success) {
            $netsuiteId = $this->parameters['netsuite_internal_id'];
        }

        $fileName = $this->getResponseFile($record, '_add');
        return include $fileName;
    }

    public function get(\NetSuite\Classes\GetRequest $getRequest)
    {
        $this->getRequest = $getRequest;
        $type = $getRequest->baseRef->type;

        $success = $this->parameters['get_success'];
        if ($success) {
            $netsuiteId = $this->parameters['netsuite_internal_id'];
        }

        $fileName = $this->getResponseFileByType($type, '_get');
        return include $fileName;
    }

    public function getList(\NetSuite\Classes\GetListRequest $getRequest)
    {
        $this->getRequest = $getRequest;
        $type = is_array($this->getRequest->baseRef) ?
            $this->getRequest->baseRef[0]->type : $this->getRequest->baseRef->type;

        $success = $this->parameters[$type]['success'];
        $productQty = $this->parameters[$type]['qty'];

        $fileName = $this->getResponseFileByType($type, '_getList');
        return include $fileName;
    }

    public function getAddRequest()
    {
        return $this->addRequest;
    }

    public function update(\NetSuite\Classes\UpdateRequest $updateRequest)
    {
        $this->updateRequest = $updateRequest;
        $record = $updateRequest->record;
        $success = $this->parameters['update_success'];

        if ($success) {
            $netsuiteId = $this->parameters['netsuite_internal_id'];
        }

        $fileName = $this->getResponseFile($record, '_update');
        return include $fileName;
    }

    public function getServerTime(\NetSuite\Classes\GetServerTimeRequest $getServerTimeRequest)
    {
        $serverTime = new \NetSuite\Classes\GetServerTimeResponse();
        $serverTime->getServerTimeResult = new \NetSuite\Classes\GetServerTimeResult();
        $serverTime->getServerTimeResult->serverTime = '2020-12-21 00:00:00';
        $serverTime->getServerTimeResult->status = new \NetSuite\Classes\Status();
        $serverTime->getServerTimeResult->status->isSuccess = true;

        return $serverTime;
    }

    public function getUpdateRequest()
    {
        return $this->updateRequest;
    }

    public function initialize(\NetSuite\Classes\InitializeRequest $initializeRequestRequest)
    {
        $this->initializeRequest = $initializeRequestRequest;
        $type = $initializeRequestRequest->initializeRecord->type;

        $success = $this->parameters['initialize_success'];

        $fileName = $this->getResponseFileByType($type, '_initialize');
        return include $fileName;
    }

    public function getInitializeRequest()
    {
        return $this->initializeRequest;
    }

    public function setSearchPreferences($bodyFieldsOnly = true, $pageSize = 50, $returnSearchColumns = true)
    {
        //
    }

    private function getResponseFile($className, $additionalParts = "")
    {
        // the response is a file content
        return rtrim(
            $this->pathToResponses,
            '/'
        ) . '/_files_ns_response/' . $this->getClassName($className) . $additionalParts;
    }

    private function getClassName($object)
    {
        return str_replace(
            '\\',
            '_',
            trim(
                str_replace("NetSuite\Classes", "", get_class($object)),
                '\\'
            )
        );
    }

    private function getResponseFileByType($type, $additionalParts = "")
    {
        // the response is a file content
        return rtrim($this->pathToResponses, '/') . '/_files_ns_response/' . ucfirst($type) . $additionalParts;
    }

    private function setCounter(string $method)
    {
        if (!isset($this->counter[$method])) {
            $this->counter[$method] = 1;
        } else {
            $this->counter[$method]++;
        }
    }
}
