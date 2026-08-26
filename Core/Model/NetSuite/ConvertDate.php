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
class ConvertDate
{
    /**
     * @param $netsuiteDateString
     * @return false|string
     */
    public static function fromNetSuiteToSql(string $netsuiteDateString)// phpcs:ignore
    {
        return date('Y-m-d H:i:s', strtotime($netsuiteDateString));
    }
}
