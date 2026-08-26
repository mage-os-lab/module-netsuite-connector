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

namespace MageOS\NetSuiteConnector\Inventory\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * This class provides an array of field types
 */
class FieldType implements OptionSourceInterface
{
    public const FIELD_TYPE_STANDARD = 'standard';
    public const FIELD_TYPE_CUSTOM = 'custom';

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $options = $this->toArray();
        $return = [];
        foreach ($options as $key => $value) {
            $return[] = ['label' => $value, 'value' => $key];
        }
        return $return;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::FIELD_TYPE_STANDARD => __('Standard'),
            self::FIELD_TYPE_CUSTOM => __('Custom'),
        ];
    }
}
