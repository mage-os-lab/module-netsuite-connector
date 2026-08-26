<?php declare(strict_types=1);
/**
 * Copyright © Mage-OS. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageOS\NetSuiteConnector\Inventory\Model\NetSuiteInventoryRepository;

/**
 * Delegates NetSuite stock search transformation to the single or multi location strategy.
 */
class StockDataTransformationResolver implements StockDataTransformationInterface
{
    public function __construct(
        private readonly \MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode $inventoryMode,
        private readonly \MageOS\NetSuiteConnector\Inventory\Single\Model\NetSuiteInventoryRepository\StockDataTransformation $singleTransformation,
        private readonly \MageOS\NetSuiteConnector\Inventory\Multi\Model\NetSuiteInventoryRepository\StockDataTransformation $multiTransformation
    ) {
    }

    /**
     * @inheritDoc
     */
    public function processSavedSearch($netsuiteService, $savedSearchId): array
    {
        return $this->resolve()->processSavedSearch($netsuiteService, $savedSearchId);
    }

    /**
     * Get the strategy for the configured inventory mode
     *
     * @return StockDataTransformationInterface
     */
    private function resolve(): StockDataTransformationInterface
    {
        if ($this->inventoryMode->isMulti()) {
            return $this->multiTransformation;
        }

        return $this->singleTransformation;
    }
}
