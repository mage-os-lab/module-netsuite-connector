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
 *
 */

declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Customer\Model\Mapper;

use MageOS\NetSuiteConnector\Product\Model\Config\ProductConfig;

/**
 * Class PriceLevel
 */
class PriceLevel
{
    /**
     * @var ProductConfig
     */
    private $productConfig;

    /**
     * @param ProductConfig $productConfig
     */
    public function __construct(ProductConfig $productConfig)
    {
        $this->productConfig = $productConfig;
    }

    /**
     * Returns the price level that corresponds to the magento customer group
     * @param string|int $groupId
     * @return null|int
     */
    public function getPriceLevelByGroupId($groupId)
    {
        foreach ($this->productConfig->getPriceLevelMap() as $priceLevelMapItem) {
            if ($priceLevelMapItem['customer_group'] == $groupId) {
                return $priceLevelMapItem['price_level'];
            }
        }

        return null;
    }

    /**
     * Returns the group id that corresponds to the netsuite price level
     * @param string|int $priceLevel
     * @return null|int
     */
    public function getGroupIdByPriceLevel($priceLevel)
    {
        foreach ($this->productConfig->getPriceLevelMap() as $priceLevelMapItem) {
            if ($priceLevelMapItem['price_level'] == $priceLevel) {
                return $priceLevelMapItem['customer_group'];
            }
        }

        return null;
    }
}
