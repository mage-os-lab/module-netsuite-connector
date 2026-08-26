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

use NetSuite\Classes\SearchRequest;
use NetSuite\Classes\TransactionSearchBasic;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\RequestPreparator;

class DefaultRequestBuilder implements RequestBuilderInterface
{
    private \Magento\Framework\Event\ManagerInterface $eventManager;
    private string $recordType;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager,
        string $recordType
    ) {
        $this->eventManager = $eventManager;
        $this->recordType = $recordType;
    }

    public function getNetsuiteRequest(array $recordIds): SearchRequest
    {
        $tranSearchBasic = new TransactionSearchBasic();
        $tranSearchBasic->type = RequestPreparator::getSearchTypeField($this->recordType);
        $tranSearchBasic->internalId = RequestPreparator::getSearchInternalIdField($recordIds);

        $this->eventManager->dispatch(
            'netsuite_import_request_before',
            [
                'record_type' => $this->recordType,
                'search_object' => $tranSearchBasic
            ]
        );

        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $tranSearchBasic;

        return $searchRequest;
    }
}
