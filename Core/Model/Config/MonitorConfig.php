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

namespace MageOS\NetSuiteConnector\Core\Model\Config;

/**
 * @method string getDebugLevel
 * @method bool getRemoveIfSuccess
 * @method int getLifetime
 */
class MonitorConfig extends AbstractConfig
{
    private const DEBUG_LEVEL = 'mageos_netsuite/monitor/debug_level';
    private const REMOVE_IF_SUCCESS = 'mageos_netsuite/monitor/remove_if_success';
    private const LIFETIME = 'mageos_netsuite/monitor/lifetime';

    public function getOptionsMap(): array
    {
        return [
            self::DEBUG_LEVEL => 'string',
            self::REMOVE_IF_SUCCESS => 'bool',
            self::LIFETIME => 'int'
        ];
    }
}
