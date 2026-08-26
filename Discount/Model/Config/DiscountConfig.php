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

namespace MageOS\NetSuiteConnector\Discount\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\AbstractConfig;

/**
 * This class provides access to configuration
 *
 * @method int getDiscountItemId
 * @method bool getDisableOrderLevelDiscount
 * @method string getLogicSwitch
 * @method bool getAddPromotionData
 * @method bool getOrderSkipDiscount
 */
class DiscountConfig extends AbstractConfig
{
    private const DISCOUNT_ITEM_ID = 'mageos_netsuite/orders/discount_item_id';
    private const DISABLE_ORDER_LEVEL_DISCOUNT = 'mageos_netsuite/orders/disable_order_level_discount';
    private const LOGIC_SWITCH = 'mageos_netsuite/orders/logic_switch';
    private const ADD_PROMOTION_DATA = 'mageos_netsuite/orders/add_promotion_data';
    private const ORDER_SKIP_DISCOUNT = 'mageos_netsuite/orders/order_skip_discount';

    public function getOptionsMap(): array
    {
        return [
            self::DISCOUNT_ITEM_ID => 'int',
            self::DISABLE_ORDER_LEVEL_DISCOUNT => 'bool',
            self::LOGIC_SWITCH => 'string',
            self::ADD_PROMOTION_DATA => 'bool',
            self::ORDER_SKIP_DISCOUNT => 'bool',
        ];
    }
}
