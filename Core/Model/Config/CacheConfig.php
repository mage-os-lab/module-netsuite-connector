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

namespace MageOS\NetSuiteConnector\Core\Model\Config;

/**
 * @method int getCacheSecondsForListsAndCustomRecords
 */
class CacheConfig extends AbstractConfig
{
    private const CACHE_SECONDS_FOR_LISTS_AND_CUSTOM_RECORDS =
        'mageos_netsuite/products/cache_seconds_for_lists_and_custom_records';

    public function getOptionsMap(): array
    {
        return [
            self::CACHE_SECONDS_FOR_LISTS_AND_CUSTOM_RECORDS => 'int'
        ];
    }
}
