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

namespace MageOS\NetSuiteConnector\Refund\Model\ConfigProvider;

/**
 * This class is responsible for the checking of the feature "Credit Memos and
 * Cash Refunds import" status (enabled/disabled)
 */
class Permissions implements \MageOS\NetSuiteConnector\Core\Model\Config\PermissionsConfigInterface
{
    public const GET_CREDIT_MEMO = 'get_credit_memo';
    public const GET_CASH_REFUND = 'get_cash_refund';
    private \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig;
    private \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->connectorConfig = $connectorConfig;
    }

    /**
     * Check whether a feature is enabled by its code
     *
     * @param $featureCode
     * @return bool
     */
    public function isFeatureEnabled($featureCode): bool
    {
        if (!$this->connectorConfig->isEnabled()) {
            return false;
        }
        if (!in_array($featureCode, [self::GET_CREDIT_MEMO, self::GET_CASH_REFUND])) {
            return false;
        }
        //coz both ns entities is used for credit memo we use one permission
        return $this->scopeConfig->isSetFlag(self::SUB_CONFIG_PATH . $featureCode);
    }
}
