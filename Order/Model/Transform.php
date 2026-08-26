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

namespace MageOS\NetSuiteConnector\Order\Model;

use MageOS\NetSuiteConnector\Order\Model\Config\SalesConfig;

/**
 * This class maps NS order status to magento order status
 */
class Transform
{
    private const DEFAULT_STATE = 'processing';

    /**
     * @var SalesConfig
     */
    private $salesConfig;

    /**
     * @param SalesConfig $salesConfig
     */
    public function __construct(SalesConfig $salesConfig)
    {
        $this->salesConfig = $salesConfig;
    }

    /**
     * Get magento order state by NetSuite order status
     *
     * @param string $netsuiteStatus
     * @return string
     */
    public function netsuiteStatusToMagentoOrderState($netsuiteStatus): string
    {
        $statusToStateMap = $this->salesConfig->getStatusMap();
        if (!is_array($statusToStateMap)) {
            return self::DEFAULT_STATE;
        }
        foreach ($statusToStateMap as $statusToStateMapItem) {
            if (strtolower($statusToStateMapItem['netsuite_status']) === strtolower($netsuiteStatus)) {
                return $statusToStateMapItem['magento_status'];
            }
        }
        //Default to processing if not math is foun
        return self::DEFAULT_STATE;
    }
}
