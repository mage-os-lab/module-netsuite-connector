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

namespace MageOS\NetSuiteConnector\Shipment\Model\Mapper;

use Magento\Framework\Exception\NoSuchEntityException;
use NetSuite\Classes\ItemFulfillment;
use NetSuite\Classes\Record;
use MageOS\NetSuiteConnector\Core\Exception\SkipRecordException;

/**
 * This interface converts NS shipment into magento shipment
 */
interface ShipmentInterface
{
    /**
     * This is an ITEM_GROUP specific field. For information on how its handled, look at the RW NS
     * General idea was:
     * - suitescript which assigns parent_id (netsuite_internal_id) of ItemGroup on IF creation
     * - we are syncing ONLY ItemGroup as Simple Product into Magento
     * - when fulfilling, we use the custcol_parent_id to map multiple items being fulfilled from ItemGroup in NS
     *   into a single product in Magento
     *
     * WARNING: This is not a fully workable solution! It was created as a proof-of-concept but its staying in the
     * branch so the idea is not lost in time.
     *
     * Code parts noted with IG-start and IG-end mark the parts developed for support of ITEM_GROUP
     */
    public const ITEM_GROUP_ITEM_FIELD = 'custcol_parent_id';

    /**
     * Convert NetSuite Fulfillment data into Magento Shipments
     * for single source it can return
     * @param Record|ItemFulfillment $netsuiteShipment
     * @return array
     * @throws SkipRecordException
     * @throws NoSuchEntityException
     */
    public function getMagentoFormat(Record $netsuiteShipment): array;
}
