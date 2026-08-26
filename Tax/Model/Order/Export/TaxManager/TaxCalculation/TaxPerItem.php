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

namespace MageOS\NetSuiteConnector\Tax\Model\Order\Export\TaxManager\TaxCalculation;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Bundle\Model\Product\Type as BundleProduct;

/**
 * Tax Amount retrieving from item
 */
class TaxPerItem
{
    /**
     * method retrieve tax amount for order item ignoring bundle items
     * @param OrderItemInterface $item
     * @param ProductInterface $product
     * @return float
     */
    public function getTaxAmount(OrderItemInterface $item, ProductInterface $product):float
    {
        $isBundle = $item->getProductType() == BundleProduct::TYPE_CODE;

        $taxAmount = (float)$item->getTaxAmount();
        if (!((float)$item->getRowTotal()) && $item->getParentItemId()) {
            if (!$isBundle || $product->getPrice() != 0) {
                $taxAmount = (float)$item->getParentItem()->getTaxAmount();
            }
        }
        if ($isBundle && $product->getPrice() == 0) {
            //We remove the tax here as a zero-priced bundle has the price of its parts
            $taxAmount = 0.0;
        }
        return $taxAmount;
    }
}
