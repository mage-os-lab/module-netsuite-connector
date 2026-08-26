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

namespace MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport;

use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SalesOrderItem;
use NetSuite\Classes\SalesOrderItemList;

/**
 * This class is responsible for managing NS itemList inside NS order
 */
class OrderItemList
{
    /**
     * Create empty itemList object for NS order
     *
     * @param SalesOrder $netsuiteOrder
     */
    public function initOrderItemList(SalesOrder $netsuiteOrder)
    {
        $netsuiteOrder->itemList = new SalesOrderItemList();
        $netsuiteOrder->itemList->item = [];
    }

    /**
     * Add given NS order item to itemList object inside NS order
     *
     * @param SalesOrder $netsuiteOrder
     * @param SalesOrderItem $netsuiteOrderItem
     */
    public function addOrderItemToList(SalesOrder $netsuiteOrder, SalesOrderItem $netsuiteOrderItem)
    {
        if (!$netsuiteOrder->itemList || !$netsuiteOrder->itemList->item) {
            $this->initOrderItemList($netsuiteOrder);
        }
        $netsuiteOrder->itemList->item[] = $netsuiteOrderItem;
    }
}
