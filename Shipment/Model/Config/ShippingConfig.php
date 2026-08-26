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

namespace MageOS\NetSuiteConnector\Shipment\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\AbstractConfig;

/**
 * This class provides access to configuration
 *
 * @method bool getSendTrackingInformationOnImport
 * @method mixed getNetsuiteMapping
 * @method int getNetsuiteDefaultShippingId
 * @method string getDefaultTrackingCodeCarrier
 * @method mixed getTrackingMapping
 */
class ShippingConfig extends AbstractConfig
{
    public const SEND_TRACKING_INFO_ON_IMPORT =
        'mageos_netsuite/shipping_methods/send_tracking_information_on_import';

    public const NETSUITE_MAPPING = 'mageos_netsuite/shipping_methods/netsuite_mapping';

    public const NETSUITE_DEFAULT_SHIPPING_ID = 'mageos_netsuite/shipping_methods/netsuite_default_shipping_id';

    public const DEFAULT_TRACKING_CODE_CARRIER = 'mageos_netsuite/shipping_methods/default_tracking_code_carrier';

    public const TRACKING_MAPPING = 'mageos_netsuite/shipping_methods/tracking_mapping';

    /**
     * @inheritdoc
     */
    public function getOptionsMap(): array
    {
        return [
            self::SEND_TRACKING_INFO_ON_IMPORT => 'bool',
            self::NETSUITE_MAPPING => 'json',
            self::NETSUITE_DEFAULT_SHIPPING_ID => 'int',
            self::DEFAULT_TRACKING_CODE_CARRIER => 'string',
            self::TRACKING_MAPPING => 'json',
        ];
    }
}
