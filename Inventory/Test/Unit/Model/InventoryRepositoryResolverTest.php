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

namespace MageOS\NetSuiteConnector\Inventory\Test\Unit\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode;
use MageOS\NetSuiteConnector\Inventory\Model\InventoryRepositoryResolver;
use MageOS\NetSuiteConnector\Inventory\Multi\Model\MultiStockRepository;
use MageOS\NetSuiteConnector\Inventory\Single\Model\SingleStockRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the inventory mode switch that picks the stock persistence strategy.
 */
class InventoryRepositoryResolverTest extends TestCase
{
    private const STOCK_DATA = [['sku' => 'SKU-1', 'qty' => 7]];
    private const PRODUCT_IDS = [11, 22];

    /**
     * In single source mode the single stock repository saves the data and the multi repository stays idle.
     */
    #[DataProvider('singleModeValueProvider')]
    public function testItDelegatesToTheSingleRepositoryOutsideMultiMode(string $configuredMode): void
    {
        $singleStockRepository = $this->createMock(SingleStockRepository::class);
        $singleStockRepository->expects($this->once())
            ->method('saveInventoryData')
            ->with(self::STOCK_DATA, self::PRODUCT_IDS)
            ->willReturn(true);
        $multiStockRepository = $this->createMock(MultiStockRepository::class);
        $multiStockRepository->expects($this->never())->method('saveInventoryData');
        $resolver = new InventoryRepositoryResolver(
            $this->inventoryMode($configuredMode),
            $singleStockRepository,
            $multiStockRepository
        );

        $this->assertTrue($resolver->saveInventoryData(self::STOCK_DATA, self::PRODUCT_IDS));
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
     * In multi source mode the multi stock repository saves the data and the single repository stays idle.
     */
    public function testItDelegatesToTheMultiRepositoryInMultiMode(): void
    {
        $multiStockRepository = $this->createMock(MultiStockRepository::class);
        $multiStockRepository->expects($this->once())
            ->method('saveInventoryData')
            ->with(self::STOCK_DATA, self::PRODUCT_IDS)
            ->willReturn(true);
        $singleStockRepository = $this->createMock(SingleStockRepository::class);
        $singleStockRepository->expects($this->never())->method('saveInventoryData');
        $resolver = new InventoryRepositoryResolver(
            $this->inventoryMode(InventoryMode::MODE_MULTI),
            $singleStockRepository,
            $multiStockRepository
        );

        $this->assertTrue($resolver->saveInventoryData(self::STOCK_DATA, self::PRODUCT_IDS));
    }

    /**
     * The return value of the selected strategy is handed back unchanged.
     */
    #[DataProvider('strategyOutcomeProvider')]
    public function testItReturnsTheOutcomeOfTheSelectedStrategy(string $configuredMode, bool $outcome): void
    {
        $singleStockRepository = $this->createStub(SingleStockRepository::class);
        $singleStockRepository->method('saveInventoryData')->willReturn($outcome);
        $multiStockRepository = $this->createStub(MultiStockRepository::class);
        $multiStockRepository->method('saveInventoryData')->willReturn($outcome);
        $resolver = new InventoryRepositoryResolver(
            $this->inventoryMode($configuredMode),
            $singleStockRepository,
            $multiStockRepository
        );

        $this->assertSame($outcome, $resolver->saveInventoryData(self::STOCK_DATA, self::PRODUCT_IDS));
    }

    /**
     * Provides each mode against each outcome the strategy can report.
     *
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function strategyOutcomeProvider(): array
    {
        return [
            'single mode saved' => [InventoryMode::MODE_SINGLE, true],
            'single mode nothing saved' => [InventoryMode::MODE_SINGLE, false],
            'multi mode saved' => [InventoryMode::MODE_MULTI, true],
            'multi mode nothing saved' => [InventoryMode::MODE_MULTI, false],
        ];
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
        $multiStockRepository = $this->createStub(MultiStockRepository::class);
        $multiStockRepository->method('saveInventoryData')->willReturn(true);
        $resolver = new InventoryRepositoryResolver(
            new InventoryMode($scopeConfig),
            $this->createStub(SingleStockRepository::class),
            $multiStockRepository
        );

        $this->assertTrue($resolver->saveInventoryData(self::STOCK_DATA, self::PRODUCT_IDS));
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
