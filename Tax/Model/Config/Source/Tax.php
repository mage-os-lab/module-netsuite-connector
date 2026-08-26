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

namespace MageOS\NetSuiteConnector\Tax\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Tax -provides logic options for the tax handling settings
 */
class Tax implements OptionSourceInterface
{
    private const TAX_HANDLING_TAX_ITEM = 'tax_item_line';
    private const TAX_HANDLING_NETSUITE_SIDE = 'netsuite_processor';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options = $this->toArray();
        $ret = [];
        foreach ($options as $key => $value) {
            $ret[] = ['label' => $value, 'value' => $key];
        }
        return $ret;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::TAX_HANDLING_TAX_ITEM => __('Send Taxes as a separate Tax Item Line'),
            self::TAX_HANDLING_NETSUITE_SIDE => __('Don\'t send Tax data, NS will calculate them'),
        ];
    }
}
