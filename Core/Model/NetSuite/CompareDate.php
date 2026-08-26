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

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite;

/**
 * Following SOLID, creating a single class with static method to handle date transformation
 * which increases readability & maintainability
 */
class CompareDate
{
    /**
     * @param string|null $magentoDateString
     * @param string|null $netsuiteDateString
     * @return false|string
     */
    public static function shouldUpdate(?string $magentoDateString, ?string $netsuiteDateString)// phpcs:ignore
    {
        /**
         * when fetching $record->lastModifiedDate the value can be null which ConvertDate returns as false
         * in that case, the record SHOULD be updated (means it is a new record)
         * same logic for magento attributes
         */
        if ($netsuiteDateString === null
            || $magentoDateString === null
            || $magentoDateString === '0000-00-00 00:00:00') {
            return true;
        }

        /**
         * If Magento Datetime is older (smaller), then we should update the record!
         * We mostly save the lastModifiedDate into Magento objects, so we need to
         * compare to smaller only (<), not smaller-or-equal (<=)
         */
        return strtotime($magentoDateString) < strtotime($netsuiteDateString);
    }

    /**
     * @param $netsuiteDateString
     * @return false|string
     */
    public static function shouldUpdateDatetime(int $magentoDateTime, $netsuiteDateString)// phpcs:ignore
    {
        /**
         * when fetching $record->lastModifiedDate the value can be null which ConvertDate returns as false
         * in that case, the record SHOULD be updated
         */
        if ($netsuiteDateString === false) {
            return true;
        }

        /**
         * If Magento Datetime is older (smaller), then we should update the record!
         */
        return $magentoDateTime < strtotime($netsuiteDateString);
    }
}
