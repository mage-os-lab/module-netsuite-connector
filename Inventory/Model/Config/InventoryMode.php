<?php declare(strict_types=1);
/**
 * Copyright © Mage-OS. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageOS\NetSuiteConnector\Inventory\Model\Config;

/**
 * Reads whether the connector maps NetSuite stock to a single Magento source or to many.
 */
class InventoryMode
{
    public const CONFIG_PATH = 'mageos_netsuite/general/inventory_mode';
    public const MODE_SINGLE = 'single';
    public const MODE_MULTI = 'multi';

    public function __construct(
        private readonly \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Get the configured inventory mode
     *
     * @return string
     */
    public function getMode(): string
    {
        $mode = (string)$this->scopeConfig->getValue(self::CONFIG_PATH);
        return $mode === self::MODE_MULTI ? self::MODE_MULTI : self::MODE_SINGLE;
    }

    /**
     * Check whether NetSuite locations map to multiple Magento sources
     *
     * @return bool
     */
    public function isMulti(): bool
    {
        return $this->getMode() === self::MODE_MULTI;
    }
}
