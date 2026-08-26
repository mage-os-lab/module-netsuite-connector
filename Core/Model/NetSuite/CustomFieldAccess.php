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
namespace MageOS\NetSuiteConnector\Core\Model\NetSuite;

use NetSuite\Classes\InventoryItem;
use NetSuite\Classes\Record;
use NetSuite\Classes\SearchColumnCustomField;

/**
 * Class CustomFieldAccess is used to simplify work with NetSuite Custom Fields
 *
 * @package MageOS\NetSuiteConnector\Core\Command\Util
 */
class CustomFieldAccess
{
    /**
     * @param \stdClass|Record|InventoryItem $record
     * @param string $scriptId
     * @param bool $throwException
     * @return mixed|null
     */
    public static function get($record, string $scriptId, $throwException = false)// phpcs:ignore
    {
        if (!is_object($record) || !isset($record->customFieldList)) {
            if ($throwException) {
                throw new \InvalidArgumentException(get_class($record) . '::customFieldList is null');
            }
            return null;
        }

        foreach ($record->customFieldList->customField as $customField) {
            if ($customField->scriptId === $scriptId) {
                if ($customField instanceof SearchColumnCustomField) {
                    return $customField->searchValue;
                }
                return $customField->value;
            }
        }

        return null;
    }

    /**
     * @param Record $record
     * @param string $scriptId
     * @return array|null
     */
    public static function getList($record, string $scriptId)// phpcs:ignore
    {
        $result = self::get($record, $scriptId);

        if (!$result) {
            return null;
        }

        $parts = explode(',', $result);
        $list = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if (!empty($part)) {
                $list[] = $part;
            }
        }

        return $list;
    }
}
