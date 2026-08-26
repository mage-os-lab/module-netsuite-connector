<?php declare(strict_types=1);
/**
 * Copyright © Mage-OS. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageOS\NetSuiteConnector\Shipment\Model\Mapper;

use NetSuite\Classes\Record;

/**
 * Delegates item fulfilment mapping to the single or multi source strategy.
 */
class ShipmentMapperResolver implements ShipmentInterface
{
    public function __construct(
        private readonly \MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode $inventoryMode,
        private readonly \MageOS\NetSuiteConnector\Shipment\SingleSource\Model\Mapper\ShipmentSingleSource $singleSourceMapper,
        private readonly \MageOS\NetSuiteConnector\Shipment\MultiSource\Model\Mapper\ShipmentMultiSource $multiSourceMapper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getMagentoFormat(Record $netsuiteShipment): array
    {
        return $this->resolve()->getMagentoFormat($netsuiteShipment);
    }

    /**
     * Get the strategy for the configured inventory mode
     *
     * @return ShipmentInterface
     */
    private function resolve(): ShipmentInterface
    {
        if ($this->inventoryMode->isMulti()) {
            return $this->multiSourceMapper;
        }

        return $this->singleSourceMapper;
    }
}
