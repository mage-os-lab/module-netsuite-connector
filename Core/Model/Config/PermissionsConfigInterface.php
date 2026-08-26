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
 *
 */

namespace MageOS\NetSuiteConnector\Core\Model\Config;

/**
 * Interface PermissionsConfigInterface - interface to be used for permission checks inside specific modules
 */
interface PermissionsConfigInterface
{
    public const SUB_CONFIG_PATH = 'mageos_netsuite/enable_disable_features/';
    /**
     * Method checks if specific NSC action allowed in configuration.
     * Usually each module have its own implementation for this.
     * @param string $featureCode
     * @return bool
     */
    public function isFeatureEnabled(string $featureCode): bool;
}
