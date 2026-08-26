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
 */

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite;

use NetSuite\Classes\CashSale;
use NetSuite\Classes\CreditMemo;
use NetSuite\Classes\InventoryItem;
use NetSuite\Classes\Invoice;
use NetSuite\Classes\ItemMatrixType;
use NetSuite\Classes\KitItem;
use NetSuite\Classes\ReturnAuthorization;

class RecordPriority
{
    // phpcs:ignore
    public static function getPriority($record)
    {
        // these depend on the existence of cashsale.. so should go last
        if ($record instanceof ReturnAuthorization || $record instanceof CreditMemo) {
            return 2;
        }
        if ($record instanceof CashSale || $record instanceof Invoice) {
            return 1;
        }
        if ($record instanceof InventoryItem) {
            if ($record->matrixType == ItemMatrixType::_parent || ($record instanceof KitItem)) {
                return 1;
            }
        }

        return 0;
    }
}
