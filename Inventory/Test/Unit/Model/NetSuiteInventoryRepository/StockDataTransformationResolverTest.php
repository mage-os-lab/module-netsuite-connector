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
 */

namespace MageOS\NetSuiteConnector\Inventory\Test\Unit\Model\NetSuiteInventoryRepository;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode;
use MageOS\NetSuiteConnector\Inventory\Model\NetSuiteInventoryRepository\StockDataTransformationResolver;
use MageOS\NetSuiteConnector\Inventory\Multi\Model\NetSuiteInventoryRepository\StockDataTransformation
    as MultiStockDataTransformation;
use MageOS\NetSuiteConnector\Inventory\Single\Model\NetSuiteInventoryRepository\StockDataTransformation
    as SingleStockDataTransformation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the inventory mode switch that picks the saved search transformation strategy.
 */
class StockDataTransformationResolverTest extends TestCase
{
    private const SAVED_SEARCH_ID = 'customsearch_stock_levels';

    /**
     * In single source mode the single transformation handles the saved search and the multi one stays idle.
     */
    #[DataProvider('singleModeValueProvider')]
    public function testItDelegatesToTheSingleTransformationOutsideMultiMode(string $configuredMode): void
    {
        $netsuiteService = new \stdClass();
        $expected = [['sku' => 'SKU-1', 'qty' => 3]];
        $singleTransformation = $this->createMock(SingleStockDataTransformation::class);
        $singleTransformation->expects($this->once())
            ->method('processSavedSearch')
            ->with($this->identicalTo($netsuiteService), self::SAVED_SEARCH_ID)
            ->willReturn($expected);
        $multiTransformation = $this->createMock(MultiStockDataTransformation::class);
        $multiTransformation->expects($this->never())->method('processSavedSearch');
        $resolver = new StockDataTransformationResolver(
            $this->inventoryMode($configuredMode),
            $singleTransformation,
            $multiTransformation
        );

        $this->assertSame($expected, $resolver->processSavedSearch($netsuiteService, self::SAVED_SEARCH_ID));
    }

    /**
     * Provides every configured value that does not select multi source mode.
     *
     * @return array<string, array{0: string}>
     */
    public static function singleModeValueProvider(): array
    {
        return [
            'explicit single mode' => [InventoryMode::MODE_SINGLE],
            'unset configuration' => [''],
            'unrecognised value' => ['multi_source'],
        ];
    }

    /**
     * In multi source mode the multi transformation handles the saved search and the single one stays idle.
     */
    public function testItDelegatesToTheMultiTransformationInMultiMode(): void
    {
        $netsuiteService = new \stdClass();
        $expected = [['sku' => 'SKU-1', 'source' => 'warehouse_a', 'qty' => 3]];
        $multiTransformation = $this->createMock(MultiStockDataTransformation::class);
        $multiTransformation->expects($this->once())
            ->method('processSavedSearch')
            ->with($this->identicalTo($netsuiteService), self::SAVED_SEARCH_ID)
            ->willReturn($expected);
        $singleTransformation = $this->createMock(SingleStockDataTransformation::class);
        $singleTransformation->expects($this->never())->method('processSavedSearch');
        $resolver = new StockDataTransformationResolver(
            $this->inventoryMode(InventoryMode::MODE_MULTI),
            $singleTransformation,
            $multiTransformation
        );

        $this->assertSame($expected, $resolver->processSavedSearch($netsuiteService, self::SAVED_SEARCH_ID));
    }

    /**
     * Flipping the configured mode swaps which transformation produces the result for the same saved search.
     */
    public function testTheConfiguredModeDecidesWhichTransformationProducesTheResult(): void
    {
        $singleResult = [['sku' => 'SKU-1', 'qty' => 3]];
        $multiResult = [['sku' => 'SKU-1', 'source' => 'warehouse_a', 'qty' => 3]];

        $this->assertSame(
            $singleResult,
            $this->resolveWith(InventoryMode::MODE_SINGLE, $singleResult, $multiResult)
        );
        $this->assertSame(
            $multiResult,
            $this->resolveWith(InventoryMode::MODE_MULTI, $singleResult, $multiResult)
        );
    }

    /**
     * The mode is read from the general inventory mode configuration path.
     */
    public function testItReadsTheModeFromTheInventoryModeConfigurationPath(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->atLeastOnce())
            ->method('getValue')
            ->with(InventoryMode::CONFIG_PATH)
            ->willReturn(InventoryMode::MODE_MULTI);
        $multiTransformation = $this->createStub(MultiStockDataTransformation::class);
        $multiTransformation->method('processSavedSearch')->willReturn([]);
        $resolver = new StockDataTransformationResolver(
            new InventoryMode($scopeConfig),
            $this->createStub(SingleStockDataTransformation::class),
            $multiTransformation
        );

        $this->assertSame([], $resolver->processSavedSearch(new \stdClass(), self::SAVED_SEARCH_ID));
    }

    /**
     * Runs the resolver once for the given mode with both strategies primed to return distinct results.
     *
     * @param string $configuredMode
     * @param array $singleResult
     * @param array $multiResult
     * @return array
     */
    private function resolveWith(string $configuredMode, array $singleResult, array $multiResult): array
    {
        $singleTransformation = $this->createStub(SingleStockDataTransformation::class);
        $singleTransformation->method('processSavedSearch')->willReturn($singleResult);
        $multiTransformation = $this->createStub(MultiStockDataTransformation::class);
        $multiTransformation->method('processSavedSearch')->willReturn($multiResult);
        $resolver = new StockDataTransformationResolver(
            $this->inventoryMode($configuredMode),
            $singleTransformation,
            $multiTransformation
        );

        return $resolver->processSavedSearch(new \stdClass(), self::SAVED_SEARCH_ID);
    }

    /**
     * Builds a real inventory mode reader backed by a stubbed scope configuration.
     *
     * @param string $configuredMode
     * @return InventoryMode
     */
    private function inventoryMode(string $configuredMode): InventoryMode
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn($configuredMode);

        return new InventoryMode($scopeConfig);
    }
}
