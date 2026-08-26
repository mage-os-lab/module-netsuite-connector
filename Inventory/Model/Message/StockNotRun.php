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

namespace MageOS\NetSuiteConnector\Inventory\Model\Message;

use MageOS\NetSuiteConnector\Core\Model\Message\NetSuiteMessage;
use MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig;

/**
 * Class StockNotRun provides message about stock update status in admin panel.
 */
class StockNotRun extends NetSuiteMessage
{
    /**
     * @var string
     */
    protected $identity = 'NETSUITE_STOCK_MESSAGE';

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getText()
    {
        return __('NetSuite stock did not run in %1 hours', $this->developerConfig->getWarnIfStockNotRunAfter());
    }

    /**
     * @return bool
     */
    public function isDisplayed()
    {
        $lastRunDate = $this->lastUpdateManager->getLastUpdateDate(StockConfig::FLAG_CODE);
        if (!$lastRunDate) {
            return true;
        }

        return time() > $this->getExpectedNextRunTimestamp(
            $this->developerConfig->getWarnIfStockNotRunAfter(),
            $lastRunDate
        );
    }
}
