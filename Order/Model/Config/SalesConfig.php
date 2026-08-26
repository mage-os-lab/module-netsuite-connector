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

namespace MageOS\NetSuiteConnector\Order\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\AbstractConfig;

/**
 * This class provides access to configuration
 *
 * @method mixed getNetsuiteMapping
 * @method mixed getProcessorMapping
 * @method mixed getCustomFieldsMapping
 * @method mixed getStatusMap
 * @method int getDiscountItemId
 * @method bool getDefaultOrderStatus
 * @method int getLocationId
 */
class SalesConfig extends AbstractConfig
{
    private const NETSUITE_MAPPING = 'mageos_netsuite/payment_methods/netsuite_mapping';
    private const PROCESSOR_MAPPING = 'mageos_netsuite/payment_methods/processor_mapping';
    private const FIELDS_MAPPING = 'mageos_netsuite/orders/custom_fields_mapping';
    private const STATUS_MAP = 'mageos_netsuite/orders/status_map';
    private const DEFAULT_ORDER_STATUS = 'mageos_netsuite/orders/default_order_status';
    private const LOCATION_ID = 'mageos_netsuite/orders/location_id';
    public function getOptionsMap(): array
    {
        return [
            self::NETSUITE_MAPPING => 'json',
            self::PROCESSOR_MAPPING => 'json',
            self::FIELDS_MAPPING => 'json',
            self::STATUS_MAP => 'json',
            self::DEFAULT_ORDER_STATUS => 'string',
            self::LOCATION_ID => 'int'
        ];
    }
}
