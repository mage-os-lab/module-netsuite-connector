<?php
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
declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Product\Model\Process\Import;

use Magento\Framework\App\Filesystem\DirectoryList;
use NetSuite\Classes\ItemSearchBasic;
use NetSuite\Classes\ItemType;
use NetSuite\Classes\Record;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SearchDateField;
use NetSuite\Classes\SearchDateFieldOperator;
use NetSuite\Classes\SearchEnumMultiSelectField;
use NetSuite\Classes\SearchEnumMultiSelectFieldOperator;
use NetSuite\Classes\SearchMoreWithIdRequest;
use NetSuite\Classes\SearchRequest;
use MageOS\NetSuiteConnector\Core\Model\Mutex;
use MageOS\NetSuiteConnector\Core\Model\Process\Import\AbstractImportProcessor;

/**
 * Import Processor for Items
 *
 * Because of dependency on AbstractImportProcessor, currently the coupling is still high (value = 18) so
 * SuppressWarning is added manually.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Item extends AbstractImportProcessor
{
    public const MESSAGE_ACTION = 'inventoryitem';
    /**
     * file-lock to be used to keep information about searchId and current page for the InventoryItems
     * import
     */
    private const IMPORT_QUEUE_INFORMATION = 'netsuite_import_queue_search_information';
    /**
     * @var array
     */
    protected $productMappers;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $productCollectionFactory;
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\Import\Item\Mapper
     */
    private $mapper;
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\Prefetch\ProcessingItem
     */
    private $prefetchProcessingItem;
    /**
     * @var \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository
     */
    private $netsuiteProductRepository;
    private \Psr\Log\LoggerInterface $logger;
    private \Magento\Framework\Filesystem $filesystem;

    protected bool $extraLoadRecordOnImport = false;

    /**
     * @param \MageOS\NetSuiteConnector\Product\Model\Import\Item\Mapper $mapper
     * @param \MageOS\NetSuiteConnector\Product\Model\ConfigProvider\Permissions $permissionHelper
     * @param \MageOS\NetSuiteConnector\Product\Model\Prefetch\ProcessingItem $prefetchProcessingItem
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Product\Model\Import\Item\Mapper $mapper,
        \MageOS\NetSuiteConnector\Product\Model\ConfigProvider\Permissions $permissionHelper,
        \MageOS\NetSuiteConnector\Product\Model\Prefetch\ProcessingItem $prefetchProcessingItem,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \Magento\Framework\Model\Context $context,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \MageOS\NetSuiteConnector\Product\Model\ResourceModel\Repository $netsuiteProductRepository,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($permissionHelper, $context, $serviceManagement);

        $this->productCollectionFactory = $productCollectionFactory;
        $this->mapper = $mapper;
        $this->prefetchProcessingItem = $prefetchProcessingItem;
        $this->netsuiteProductRepository = $netsuiteProductRepository;
        $this->logger = $context->getLogger();
        $this->filesystem = $filesystem;
    }

    /**
     * Method replaces Abstract call. Refactor of Abstract class needed before we can decouple this.
     */
    public function getNetsuiteRequest($recordType, string $startDateTime)
    {
        $now = new \DateTime($this->serviceManagement->getServerTime());

        $searchDateField = new SearchDateField();
        $searchDateField->searchValue = $startDateTime;
        $searchDateField->searchValue2 = $now->format(\DateTime::ISO8601);
        $searchDateField->operator = SearchDateFieldOperator::within;

        $typeField = new SearchEnumMultiSelectField();
        $typeField->operator = SearchEnumMultiSelectFieldOperator::anyOf;
        $typeField->searchValue[] = ItemType::_inventoryItem;
        $typeField->searchValue[] = ItemType::_assembly;
        $typeField->searchValue[] = ItemType::_kit;
        $typeField->searchValue[] = ItemType::_nonInventoryItem;
        $typeField->searchValue[] = ItemType::_itemGroup;

        $tranSearchBasic = new ItemSearchBasic();
        $tranSearchBasic->lastModifiedDate = $searchDateField;
        $tranSearchBasic->type = $typeField;

        $this->eventManager->dispatch(
            'netsuite_import_request_before',
            ['record_type' => $recordType, 'search_object' => $tranSearchBasic]
        );

        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $tranSearchBasic;

        return $searchRequest;
    }

    public function getPermissionName()
    {
        return \MageOS\NetSuiteConnector\Product\Model\ConfigProvider\Permissions::GET_PRODUCTS;
    }

    public function getRecordType()
    {
        return RecordType::inventoryItem;
    }

    public function getMessageType()
    {
        return self::MESSAGE_ACTION;
    }

    public function process(Record $inventoryItem)
    {
        $importRows = null;
        $isImportable = $this->isMagentoImportable($inventoryItem);

        try {
            if (!$isImportable) {
                $internalId = $inventoryItem->internalId;
                $exists = $this->netsuiteProductRepository->countProductsByNetSuiteIds([$internalId]);
                if (!isset($exists[$internalId])) {
                    return null;
                }
            }
            $importRows = $this->mapper->getInstance($inventoryItem)->mapInventoryItemToRowList(
                $inventoryItem,
                $isImportable
            );
        } catch (\Exception $e) {
            if (!$isImportable) {
                $importRows = null;
            } else {
                throw $e;
            }
        }

        return $importRows;
    }

    public function isAlreadyImported(Record $record)
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToFilter('netsuite_internal_id', $record->internalId);
        $productCollection->load();
        return $productCollection->count() > 0;
    }

    public function isActive()
    {
        return true;
    }

    public function isMagentoImportable(Record $inventoryItem)
    {
        return $this->mapper->getInstance($inventoryItem)->isMagentoImportable($inventoryItem);
    }

    /**
     * @param $records
     */
    public function prefetchProducts($records)
    {
        $this->prefetchProcessingItem->prefetchProducts($records);
    }

    /**
     * @param $startDateTime
     * @param bool $fromBeginning
     * @return bool
     * @throws \Exception
     */
    public function queryNetsuite($startDateTime, $fromBeginning = true)
    {
        static $response = null;
        static $searchId = null;
        static $totalPages = null;
        //current page to start from for 2 iteration is used only for cases when we start not from the beginning.
        static $currentPage = 2;
        /*
         * check that it is first call and try to retrieve the previous searchId and page number,
         * if they exists we going to start with last success page
         */
        if ($fromBeginning) {
            $searchInfo = $this->getCurrentSearchIdAndPage();
            if (!empty($searchInfo['searchId'])
                && !empty($searchInfo['pageNumber'])
                && !empty($searchInfo['totalPages'])
            ) {
                $fromBeginning = false;
                $searchId = $searchInfo['searchId'];
                $currentPage = $searchInfo['pageNumber'];
                $totalPages = $searchInfo['totalPages'];
            }
        }

        $permissionName = $this->getPermissionName();
        if (trim($permissionName) && !$this->permissionHelper->isFeatureEnabled($permissionName)) {
            return false;
        }

        $netsuiteService = $this->serviceManagement->get();
        $this->setSearchPreferences();

        if ($fromBeginning) {
            $searchRequest = $this->getNetsuiteRequest($this->getRecordType(), $startDateTime);
            $response = $netsuiteService->search($searchRequest);
            if ($response->searchResult->status->isSuccess) {
                $totalPages = $response->searchResult->totalPages;
                $searchId = $response->searchResult->searchId;
                return $response->searchResult->recordList->record;
            }
            // phpcs:ignore
            throw new \RuntimeException((string)print_r($response->searchResult->status->statusDetail, true));
        }
        if ($currentPage > $totalPages) {
            $this->deleteFile();
            return false;
        }
        $searchMoreRequest = new SearchMoreWithIdRequest();
        $searchMoreRequest->pageIndex = $currentPage;
        $searchMoreRequest->searchId = $searchId;

        $searchResponse = $netsuiteService->searchMoreWithId($searchMoreRequest);
        $currentPage++;
        if ($searchResponse->searchResult->status->isSuccess) {
            $this->saveCurrentSearchIdAndPage((string)$searchId, (string)$currentPage, (string)$totalPages);
            return $searchResponse->searchResult->recordList->record;
        }
        $this->deleteFile();
        // phpcs:ignore
        throw new \RuntimeException((string)print_r($searchResponse->searchResult->status->statusDetail, true));
    }

    /**
     * Method return array with searchId, last page, total pages of last search from file saved in specific directory
     * to avoid the double work in case of urgent stopping of inventoryItems importing to queue
     * if file not found or any other handled error took place we log and start from beginning
     */
    public function getCurrentSearchIdAndPage(): array
    {
        $result = [];
        $directory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
        if ($directory->isFile($this->getFilePath())) {
            try {
                $result = json_decode($directory->readFile($this->getFilePath()), true);
            } catch (\Throwable $e) {
                $this->logger->error('[Error during InventoryItem importing]: ' . $e->getMessage());
            }
        }
        return $result;
    }

    /**
     * methods saves the information about records importing process as json encoded associated array
     * [
     *  'searchId'=> ...,
     *  'pageNumber'=> ...,
     *  'totalPages'=> ...
     *  ]
     * @param string $searchId
     * @param string $pageNumber
     * @param string $totalPages
     */
    public function saveCurrentSearchIdAndPage(string $searchId, string $pageNumber, string $totalPages): void
    {
        try {
            $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $directory->writeFile(
                $this->getFilePath(),
                json_encode(
                    [
                        'searchId' => $searchId,
                        'pageNumber' => $pageNumber,
                        'totalPages' => $totalPages
                    ]
                )
            );
        } catch (\Throwable $e) {
            $this->logger->error('[Error during InventoryItem importing]: ' . $e->getMessage());
        }
    }

    /**
     * deleting file with search info when we do not need it anymore
     */
    private function deleteFile(): void
    {
        try {
            $directory = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            if ($directory->isFile($this->getFilePath())) {
                $directory->delete($this->getFilePath());
            }
        } catch (\Throwable $e) {
            $this->logger->error('[Error during InventoryItem importing]: ' . $e->getMessage());
        }
    }

    /**
     * returns filepath for saving the file with queue process details
     */
    private function getFilePath(): string
    {
        return Mutex::NETSUITE_TMP_DIR . DIRECTORY_SEPARATOR . self::IMPORT_QUEUE_INFORMATION;
    }
}
