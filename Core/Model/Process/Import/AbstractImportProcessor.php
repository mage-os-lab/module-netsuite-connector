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

namespace MageOS\NetSuiteConnector\Core\Model\Process\Import;

use Magento\Framework\App\ObjectManager;
use NetSuite\Classes\Record;
use NetSuite\Classes\SearchMoreWithIdRequest;
use NetSuite\Classes\SearchRequest;
use NetSuite\Classes\TransactionSearchBasic;
use MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\RequestPreparator;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;

/**
 * This is the Abstract Import Processor class.
 * Because there are bunch of \NetSuite\Classes\* references, phpMd is complaining about coupling between objects.
 * Ignoring it.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
abstract class AbstractImportProcessor implements ImportProcessorInterface
{
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Config\PermissionsConfigInterface $permissionHelper
     */
    protected $permissionHelper;
    /**
     * @var \Magento\Framework\Event\ManagerInterface $eventManager
     */
    protected $eventManager;

    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig|null
     */
    private $developerConfig;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management|null
     */
    protected $serviceManagement;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface|null
     */
    private $messageManagement;

    protected bool $extraLoadRecordOnImport = true;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Config\PermissionsConfigInterface $permissionHelper,
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        ?\MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig $developerConfig = null,
        ?\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface $messageManagement = null
    ) {
        $this->permissionHelper = $permissionHelper;
        $this->eventManager = $context->getEventDispatcher();
        $this->serviceManagement = $serviceManagement;

        $this->developerConfig = $developerConfig ?: ObjectManager::getInstance()
            ->get(\MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig::class);
        $this->messageManagement = $messageManagement ?: ObjectManager::getInstance()
            ->get(\MageOS\NetSuiteConnector\Core\Api\MessageManagementInterface::class);
    }

    /**
     * checks whether an entry is Magento importable, i.e. if the associated order also exists in Magento.
     * The orders that are created directly in NetSuite are not to be imported and managed in Magento (imported
     * only if NetSuiteImportOrders is installed)
     *
     * @param Record $record
     * @return mixed
     */
    abstract public function isMagentoImportable(Record $record);

    /**
     * returns the type of the element to be added in the export/import queue.
     * See the constants in \MageOS\NetSuiteConnector\Core\Model\Queue\Message
     *
     * @return mixed
     */
    abstract public function getMessageType();

    //process a record returned from Netsuite to be added to Magento
    abstract public function process(Record $record);

    //return the RecordType name for this entity
    abstract public function getRecordType();

    abstract public function isActive();

    abstract public function getPermissionName();

    //queries Netsuite for latest modified entries (shipments, invoices etc)
    public function queryNetsuite($startDateTime, $fromBeginning = true)
    {
        static $response = null;
        static $currentPage = 2;

        /**
         * TODO: why do we have this check here?
         * upd. import processors does not check it. may we could move it to Process class
         */
        $permissionName = $this->getPermissionName();
        if (trim((string)$permissionName) && !$this->permissionHelper->isFeatureEnabled($permissionName)) {
            return false;
        }

        $this->setSearchPreferences();
        $netsuiteService = $this->serviceManagement->get();

        if ($fromBeginning) {
            $searchRequest = $this->getNetsuiteRequest($this->getRecordType(), $startDateTime);
            $response = $netsuiteService->search($searchRequest);
            try {
                ResponseValidator::validate($response);
            } catch (DataIntegrityException $e) {
                $message = sprintf(
                    'NetSuite Response for RecordType %s returned: %s',
                    $this->getRecordType(),
                    $e->getMessage()
                );
                throw new DataIntegrityException($message);
            }

            return $response->searchResult->recordList->record;
        }

        $totalPages = $response->searchResult->totalPages;
        $searchId = $response->searchResult->searchId;

        if ($currentPage > $totalPages) {
            return false;
        }

        $searchMoreRequest = new SearchMoreWithIdRequest();
        $searchMoreRequest->pageIndex = $currentPage;
        $searchMoreRequest->searchId = $searchId;

        $searchResponse = $netsuiteService->searchMoreWithId($searchMoreRequest);
        ResponseValidator::validate($searchResponse);
        $currentPage++;
        return $searchResponse->searchResult->recordList->record;
    }

    /**
     * @param string $recordType
     * @param string $startDateTime
     * @return \NetSuite\Classes\SearchRequest
     * @throws \Exception
     */
    public function getNetsuiteRequest($recordType, string $startDateTime)
    {
        $now = new \DateTime($this->serviceManagement->getServerTime());
        $now = $now->format(\DateTime::ISO8601);

        $tranSearchBasic = new TransactionSearchBasic();
        $tranSearchBasic->lastModifiedDate = RequestPreparator::getSearchDateField($startDateTime, $now);
        $tranSearchBasic->type = RequestPreparator::getSearchTypeField($recordType);

        $this->eventManager->dispatch(
            'netsuite_import_request_before',
            [
                'record_type' => $this->getRecordType(),
                'search_object' => $tranSearchBasic
            ]
        );

        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $tranSearchBasic;

        return $searchRequest;
    }

    /**
     *
     */
    protected function setSearchPreferences()
    {
        $this->serviceManagement->get()->setSearchPreferences(false, $this->getRecordLimit());
    }

    /**
     * How many records to query from NS
     *
     * @return int
     */
    protected function getRecordLimit(): int
    {
        return (int)$this->developerConfig->getImportRecordLimit();
    }

    public function shouldExtraLoadRecordOnImport() : bool
    {
        return $this->extraLoadRecordOnImport;
    }
}
