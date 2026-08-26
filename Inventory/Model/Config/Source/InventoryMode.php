<?php declare(strict_types=1);
/**
 * Copyright © Mage-OS. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageOS\NetSuiteConnector\Inventory\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode as ModeConfig;

class InventoryMode implements OptionSourceInterface
{
    /**
     * Get the selectable inventory modes
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => ModeConfig::MODE_SINGLE,
                'label' => __('Single location')
            ],
            [
                'value' => ModeConfig::MODE_MULTI,
                'label' => __('Multiple locations')
            ]
        ];
    }
}
