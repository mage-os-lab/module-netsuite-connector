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

namespace MageOS\NetSuiteConnector\Refund\Model\Process\Import;

use NetSuite\Classes\SearchDateField;
use NetSuite\Classes\SearchDateFieldOperator;
use NetSuite\Classes\SearchEnumMultiSelectField;
use NetSuite\Classes\SearchEnumMultiSelectFieldOperator;
use NetSuite\Classes\SearchRequest;
use NetSuite\Classes\TransactionSearchBasic;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate;
use MageOS\NetSuiteConnector\Core\Model\Process\Import\AbstractImportProcessor;
use NetSuite\Classes\Record;
use NetSuite\Classes\RecordType;

/**
 * This class processes a Cash Refund record object imported from NS and create new credit memos in magento DB. Skip
 * processing in case of existing magento credit memos
 * suppressed phpmd due to high coupling - should be fixed after AbstractImportProcessor refactored
 * @suppressWarnings(PHPMD)
 */
class CashRefund extends AbstractImportProcessor
{
    public const MESSAGE_TYPE = 'cash_refund_import';
    private int $recordLimit;
    private \MageOS\NetSuiteConnector\Refund\Model\MagentoCreditMemoRepository $creditMemoRepository;

    /**
     * CreditMemo constructor.
     * @param \MageOS\NetSuiteConnector\Refund\Model\ConfigProvider\Permissions $permissionHelper
     * @param \Magento\Framework\Model\Context $context
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement
     * @param \MageOS\NetSuiteConnector\Refund\Model\MagentoCreditMemoRepository $creditMemoRepository
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Refund\Model\ConfigProvider\Permissions $permissionHelper,
        \Magento\Framework\Model\Context $context,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\Management $serviceManagement,
        \MageOS\NetSuiteConnector\Refund\Model\MagentoCreditMemoRepository $creditMemoRepository,
        $recordLimit = 10
    ) {
        parent::__construct($permissionHelper, $context, $serviceManagement);
        $this->recordLimit = $recordLimit;
        $this->creditMemoRepository = $creditMemoRepository;
    }

    /**
     * @inheritdoc
     */
    public function getPermissionName()
    {
        return \MageOS\NetSuiteConnector\Refund\Model\ConfigProvider\Permissions::GET_CASH_REFUND;
    }

    /**
     * @inheritdoc
     */
    public function isMagentoImportable(Record $record)
    {
        return true;
    }

    /**
     * Check whether given cashRefund record is already imported
     *
     * @param Record $record
     * @return boolean
     */
    public function isAlreadyImported(Record $record): bool
    {
        $return = $this->creditMemoRepository->getCreditMemoByNetSuiteId((int)$record->internalId);
        if (!$return) {
            return false;
        }

        $lastImportDate = $return->getCustomAttribute('netsuite_last_import_date');
        if (!$lastImportDate) {
            return false;
        }

        $netsuiteUpdateDatetime = ConvertDate::fromNetSuiteToSql($record->lastModifiedDate);
        if (strtotime($lastImportDate->getValue()) > strtotime($netsuiteUpdateDatetime)) {
            return true;
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getRecordType()
    {
        return RecordType::cashRefund;
    }

    /**
     * @inheritdoc
     */
    public function getMessageType()
    {
        return self::MESSAGE_TYPE;
    }

    /**
     * @inheritdoc
     */
    public function isActive()
    {
        return $this->permissionHelper->isFeatureEnabled($this->getPermissionName());
    }

    /**
     * Create new magento creditMemo based on NS data from given $record
     *
     * @param Record $record
     * @throws \Exception
     */
    public function process(Record $record)
    {
        $this->eventManager->dispatch(
            'netsuite_cashrefund_import_before',
            ['netsuite_cashrefund' => $record]
        );

        $magentoCreditMemo = $this->creditMemoRepository->createCreditMemo($record);
        if ($magentoCreditMemo === null) {
            throw new \RuntimeException('Failed to import cash sale [null]');
        }
        $this->eventManager->dispatch(
            'netsuite_cashrefund_import_after',
            ['netsuite_cashrefund' => $record, 'magento_creditmemo' => $magentoCreditMemo]
        );
    }

    /**
     * @inheritdoc
     */
    protected function getRecordLimit(): int
    {
        return $this->recordLimit;
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

        $searchDateField = new SearchDateField();
        $searchDateField->searchValue = $startDateTime;
        $searchDateField->searchValue2 = $now->format(\DateTime::ISO8601);
        $searchDateField->operator = SearchDateFieldOperator::within;

        $typeField = new SearchEnumMultiSelectField();
        $typeField->operator = SearchEnumMultiSelectFieldOperator::anyOf;
        $typeField->searchValue = \NetSuite\Classes\TransactionType::_cashRefund;

        $tranSearchBasic = new TransactionSearchBasic();
        $tranSearchBasic->lastModifiedDate = $searchDateField;
        $tranSearchBasic->type = $typeField;

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
}
