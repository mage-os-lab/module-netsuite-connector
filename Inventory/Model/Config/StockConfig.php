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

namespace MageOS\NetSuiteConnector\Inventory\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\AbstractConfig;

/**
 * This class provides access to configuration
 *
 * @method string getStockLocation
 * @method string getCustomSearchId
 * @method int getCustomSearchPageSize
 * @method string getQtyFieldName
 * @method string getQtyFieldType
 * @method bool getStockStoredAtLocationLevel
 * @method bool getChangeStockStatusUnderZero
 * @method int getUpdateStocksEveryNMinutes
 * @method int getSame
 */
class StockConfig extends AbstractConfig
{
    public const FLAG_CODE = 'last_stock_update_date';
    public const CONNECTION_SUBPATH = 'connection_stock';
    private const STOCK_LOCATION = 'mageos_netsuite/stock/stock_location';
    private const CUSTOM_SEARCH_ID = 'mageos_netsuite/stock/custom_search_id';
    private const CUSTOM_SEARCH_PAGE_SIZE = 'mageos_netsuite/stock/custom_search_page_size';
    private const QTY_FIELD_NAME = 'mageos_netsuite/stock/qty_field_name';
    private const QTY_FIELD_TYPE = 'mageos_netsuite/stock/qty_field_type';
    private const STOCK_STORED_AT_LOCATION_LEVEL = 'mageos_netsuite/stock/stock_stored_at_location_level';
    private const CHANGE_STOCK_STATUS_UNDER_ZERO = 'mageos_netsuite/stock/change_stock_status_under_zero';
    private const UPDATE_STOCKS_EVERY_N_MINUTES = 'mageos_netsuite/stock/update_stocks_every_n_minutes';
    private const CONNECTION_STOCK_SAME = 'mageos_netsuite/connection_stock/same';

    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\LastUpdateManager $lastUpdateManager;

    /**
     * StockConfig constructor.
     * @param \MageOS\NetSuiteConnector\Core\Model\NetSuite\LastUpdateManager $lastUpdateManager
     * @param \MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolverFactory $configFactory
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\LastUpdateManager $lastUpdateManager,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolverFactory $configFactory
    ) {
        parent::__construct($configFactory);
        $this->lastUpdateManager = $lastUpdateManager;
    }

    /**
     * @return array
     */
    public function getOptionsMap(): array
    {
        return [
            self::STOCK_LOCATION => 'int',
            self::CUSTOM_SEARCH_ID => 'string',
            self::CUSTOM_SEARCH_PAGE_SIZE => 'int',
            self::QTY_FIELD_NAME => 'string',
            self::QTY_FIELD_TYPE => 'string',
            self::STOCK_STORED_AT_LOCATION_LEVEL => 'bool',
            self::CHANGE_STOCK_STATUS_UNDER_ZERO => 'bool',
            self::UPDATE_STOCKS_EVERY_N_MINUTES => 'int',
            self::CONNECTION_STOCK_SAME => 'int',
        ];
    }

    /**
     * Check whether stock import can be processed OR it should wait
     *
     * @param $nowAsSqlDate
     * @return bool
     * @throws \Exception
     */
    public function shouldRun($nowAsSqlDate): bool
    {
        $lastInventoryUpdateDate = $this->lastUpdateManager->getLastUpdateDate(self::FLAG_CODE);
        $updateEveryNMinutes = $this->getUpdateStocksEveryNMinutes();

        /**
         * If flag is not set (first time running)
         * or update_stock_every_n_minutes equals 0
         * then update should always run
         */
        if (!$updateEveryNMinutes || !$lastInventoryUpdateDate) {
            return true;
        }

        $lastInventoryUpdateDate = \DateTime::createFromFormat('Y-m-d H:i:s', $lastInventoryUpdateDate);
        $lastInventoryUpdateDate->add(new \DateInterval(sprintf('PT%sM', $updateEveryNMinutes)));

        $nowTimestamp = strtotime($nowAsSqlDate);

        return $lastInventoryUpdateDate->getTimestamp() <= $nowTimestamp;
    }
}
