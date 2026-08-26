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

namespace MageOS\NetSuiteConnector\Inventory\Model\ConfigProvider;

/**
 * Class Permissions provides data about enable/disable of stock update integration.
 */
class Permissions implements \MageOS\NetSuiteConnector\Core\Model\Config\PermissionsConfigInterface
{
    public const GET_STOCK_UPDATES = 'mageos_netsuite/enable_disable_features/get_stock_updates';

    private \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig;
    private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig
    ) {

        $this->connectorConfig = $connectorConfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param $featureCode
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function isFeatureEnabled($featureCode = ''): bool
    {
        if (!$this->connectorConfig->isEnabled()) {
            return false;
        }
        return $this->scopeConfig->isSetFlag(self::GET_STOCK_UPDATES);
    }
}
