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

namespace MageOS\NetSuiteConnector\CustomerImport\Model\Command\RequestBuilder;

use NetSuite\Classes\CustomerSearchBasic;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SearchBooleanField;
use NetSuite\Classes\SearchRequest;
use MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord\RequestBuilderInterface;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\RequestPreparator;

class CustomerImport implements RequestBuilderInterface
{
    private \Magento\Framework\Event\ManagerInterface $eventManager;

    public function __construct(
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    public function getNetsuiteRequest(array $recordIds): SearchRequest
    {
        $customerSearchBasic = new CustomerSearchBasic();
        $customerSearchBasic->isPerson = new SearchBooleanField();
        $customerSearchBasic->isPerson->searchValue = true;
        $customerSearchBasic->internalId = RequestPreparator::getSearchInternalIdField($recordIds);

        $this->eventManager->dispatch(
            'netsuite_import_request_before',
            [
                'record_type' => RecordType::customer,
                'search_object' => $customerSearchBasic
            ]
        );

        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $customerSearchBasic;

        return $searchRequest;
    }
}
