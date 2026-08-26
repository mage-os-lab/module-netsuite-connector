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

namespace MageOS\NetSuiteConnector\Product\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SpecialPrice implements OptionSourceInterface
{
    public const OPTION_NO = 0;
    public const OPTION_UPDATE = 1;
    public const OPTION_REPLACE = 2;

    public function toOptionArray(): array
    {
        return [
            ['value' => self::OPTION_NO, 'label' => __('Disabled')],
            ['value' => self::OPTION_UPDATE, 'label' => __('Update Only')],
            ['value' => self::OPTION_REPLACE, 'label' => __('Replace')],
        ];
    }
}
