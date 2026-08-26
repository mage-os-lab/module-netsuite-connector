<?php declare(strict_types=1);
/**
 * Copyright © Mage-OS. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace MageOS\NetSuiteConnector\Inventory\Model;

/**
 * Delegates stock persistence to the single or multi location strategy.
 */
class InventoryRepositoryResolver implements MagentoInventoryRepositoryInterface
{
    public function __construct(
        private readonly \MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode $inventoryMode,
        private readonly \MageOS\NetSuiteConnector\Inventory\Single\Model\SingleStockRepository $singleStockRepository,
        private readonly \MageOS\NetSuiteConnector\Inventory\Multi\Model\MultiStockRepository $multiStockRepository
    ) {
    }

    /**
     * @inheritDoc
     */
    public function saveInventoryData(array $stockData, array $productIdsToReindex): bool
    {
        return $this->resolve()->saveInventoryData($stockData, $productIdsToReindex);
    }

    /**
     * Get the strategy for the configured inventory mode
     *
     * @return MagentoInventoryRepositoryInterface
     */
    private function resolve(): MagentoInventoryRepositoryInterface
    {
        if ($this->inventoryMode->isMulti()) {
            return $this->multiStockRepository;
        }

        return $this->singleStockRepository;
    }
}
