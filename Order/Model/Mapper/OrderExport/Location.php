<?php declare(strict_types=1);
/*
 *   RocketWeb
 *
 *  NOTICE OF LICENSE
 *
 *  This source file is subject to the Open Software License (OSL 3.0)
 *  that is bundled with this package in the file LICENSE.txt.
 *  It is also available through the world-wide-web at this URL:
 *  http://opensource.org/licenses/osl-3.0.php
 *
 *  @category  RocketWeb
 *  @package   MageOS_NetSuiteConnector
 *  @copyright Copyright (c) 2026 RocketWeb (http://rocketweb.com)
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  @author    Rocket Web Inc.
 *
 */

namespace MageOS\NetSuiteConnector\Order\Model\Mapper\OrderExport;

use NetSuite\Classes\RecordRef;
use NetSuite\Classes\RecordType;
use NetSuite\Classes\SalesOrder;
use NetSuite\Classes\SalesOrderItem;

/**
 * This class is responsible for adding NS Location to NS order items. This is used for a magento order export.
 */
class Location
{
    public function __construct(
        private readonly \MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig $salesConfig
    ) {
    }

    public function addLocation(SalesOrder | SalesOrderItem $netsuiteItem): void
    {
        $locationId = (int)$this->salesConfig->getLocationId();
        if ($locationId <= 0) {
            return;
        }
        $netsuiteItem->location = new RecordRef();
        $netsuiteItem->location->type = RecordType::location;
        $netsuiteItem->location->internalId = $locationId;
    }
}
