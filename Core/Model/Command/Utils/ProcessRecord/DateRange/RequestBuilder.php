<?php declare(strict_types=1);
/**
 *  RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 * @category  RocketWeb
 * @package   MageOS_NetSuiteConnector
 * @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 * @author    Rocket Web Inc.
 */
namespace MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord\DateRange;

use NetSuite\Classes\SearchCustomFieldList;
use NetSuite\Classes\SearchDateField;
use NetSuite\Classes\SearchDateFieldOperator;
use NetSuite\Classes\SearchRequest;
use NetSuite\Classes\TransactionSearchBasic;
use MageOS\NetSuiteConnector\Core\Model\Command\Utils\ProcessRecord\RequestBuilderInterface;
use MageOS\NetSuiteConnector\Core\Enum\Record\DateRange;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\RequestPreparator;

class RequestBuilder implements RequestBuilderInterface
{
    /**
     * Prepare request to NetSuite
     *
     * @param array $recordIds
     * @return SearchRequest
     */
    public function getNetsuiteRequest(array $recordIds): SearchRequest
    {
        // Search Type
        $tranSearchBasic = new TransactionSearchBasic();
        $tranSearchBasic->type = RequestPreparator::getSearchTypeField($recordIds[DateRange::TYPE->value]);

        // Date range filter, field - created_at
        $searchDateField = new SearchDateField();
        $searchDateField->searchValue = $this->getFormattedDate($recordIds[DateRange::FROM_DATE->value]);
        $searchDateField->searchValue2 = $this->getFormattedDate($recordIds[DateRange::TO_DATE->value]);
        $searchDateField->operator = SearchDateFieldOperator::within; // @phpstan-ignore-line
        $tranSearchBasic->dateCreated = $searchDateField;

        //Search request entity
        $searchRequest = new SearchRequest();
        $searchRequest->searchRecord = $tranSearchBasic;
        return $searchRequest;
    }

    private function getFormattedDate(string $date): string
    {
        $date = new \DateTime($date, new \DateTimeZone('GMT-8'));
        return $date->format(\DateTime::ISO8601);
    }
}
