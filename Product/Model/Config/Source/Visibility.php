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

namespace MageOS\NetSuiteConnector\Product\Model\Config\Source;

use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * This class provides an array of product visibilities as value-label options
 */
class Visibility implements OptionSourceInterface
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => ProductVisibility::VISIBILITY_NOT_VISIBLE, 'label' => __('Not Visible')],
            ['value' => ProductVisibility::VISIBILITY_IN_CATALOG, 'label' => __('Catalog')],
            ['value' => ProductVisibility::VISIBILITY_IN_SEARCH, 'label' => __('Search')],
            ['value' => ProductVisibility::VISIBILITY_BOTH, 'label' => __('Catalog, Search')],
        ];
    }
}
