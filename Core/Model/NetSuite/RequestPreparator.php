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

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite;

use NetSuite\Classes\RecordRef;
use NetSuite\Classes\SearchDateField;
use NetSuite\Classes\SearchDateFieldOperator;
use NetSuite\Classes\SearchEnumMultiSelectField;
use NetSuite\Classes\SearchEnumMultiSelectFieldOperator;
use NetSuite\Classes\SearchMultiSelectField;
use NetSuite\Classes\SearchMultiSelectFieldOperator;

/**
 * This is a helper class to generate standard Search fields that we use so we can keep
 * the code more clean and it lowers Coupling between classes calling this code (3 => 1 or 2 => 1)
 */
class RequestPreparator
{
    // phpcs:ignore
    public static function getSearchTypeField(string $type): SearchEnumMultiSelectField
    {
        $typeField = new SearchEnumMultiSelectField();
        $typeField->operator = SearchEnumMultiSelectFieldOperator::anyOf;
        $typeField->searchValue = $type;

        return $typeField;
    }

    // phpcs:ignore
    public static function getSearchInternalIdField(array $recordIds): SearchMultiSelectField
    {
        $internalIdField = new SearchMultiSelectField();
        $internalIdField->operator = SearchMultiSelectFieldOperator::anyOf;
        $internalIdField->searchValue = [];

        foreach ($recordIds as $recordId) {
            $recordRef = new RecordRef();
            $recordRef->internalId = $recordId;
            $internalIdField->searchValue[] = $recordRef;
        }

        return $internalIdField;
    }

    // phpcs:ignore
    public static function getSearchDateField(string $startDateTime, string $endDateTime): SearchDateField
    {
        $searchDateField = new SearchDateField();
        $searchDateField->searchValue = $startDateTime;
        $searchDateField->searchValue2 = $endDateTime;
        $searchDateField->operator = SearchDateFieldOperator::within;

        return $searchDateField;
    }
}
