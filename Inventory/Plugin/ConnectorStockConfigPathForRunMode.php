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

namespace MageOS\NetSuiteConnector\Inventory\Plugin;

use MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig;

/**
 * This class updates the logic of retrieving system config path - it adds new mode "stock"
 */
class ConnectorStockConfigPathForRunMode
{
    private \MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig $stockConfig;

    /**
     * ConnectorStockConfigPathForRunMode constructor.
     * @param \MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig $stockConfig
     */
    public function __construct(\MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig $stockConfig)
    {
        $this->stockConfig = $stockConfig;
    }

    /**
     * Replace original path for connection details in case of a separate connection for stock import is defined
     *
     * @param ConnectorConfig $subject
     * @param string $result
     * @param string $runMode
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSystemConfigPathForRunMode(
        ConnectorConfig $subject,
        string $result,
        $runMode
    ): string {
        if ($runMode == NetSuiteCronPlugin::STOCK_MODE && $this->stockConfig->getSame() == 0) {
            $result = \MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig::CONNECTION_SUBPATH;
        }

        return $result;
    }
}
