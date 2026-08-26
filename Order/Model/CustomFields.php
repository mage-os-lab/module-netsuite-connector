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

namespace MageOS\NetSuiteConnector\Order\Model;

/**
 * This class keeps public constants for customField types and customField value types
 */
class CustomFields
{
    public const TYPE_SIMPLE = 'simple';
    public const TYPE_LIST = 'list';
    public const TYPE_STANDARD = 'standard';
    public const TYPE_STANDARD_RECORD_REF = 'standard_record_ref';
    public const VALUE_TYPE_FIXED = 'fixed';
    public const VALUE_TYPE_ORDER_ATTRIBUTE = 'order_attribute';
}
