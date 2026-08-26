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

namespace MageOS\NetSuiteConnector\Shipment\Test\Unit\Model\Mapper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode;
use MageOS\NetSuiteConnector\Shipment\Model\Mapper\ShipmentMapperResolver;
use MageOS\NetSuiteConnector\Shipment\MultiSource\Model\Mapper\ShipmentMultiSource;
use MageOS\NetSuiteConnector\Shipment\SingleSource\Model\Mapper\ShipmentSingleSource;
use NetSuite\Classes\Record;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the inventory mode switch that picks the item fulfilment mapper.
 */
class ShipmentMapperResolverTest extends TestCase
{
    /**
     * In single source mode the single source mapper handles the record and the multi source mapper stays idle.
     */
    #[DataProvider('singleModeValueProvider')]
    public function testItDelegatesToTheSingleSourceMapperOutsideMultiMode(string $configuredMode): void
    {
        $record = new Record();
        $expected = [['shipment' => 'single']];
        $singleSourceMapper = $this->createMock(ShipmentSingleSource::class);
        $singleSourceMapper->expects($this->once())
            ->method('getMagentoFormat')
            ->with($this->identicalTo($record))
            ->willReturn($expected);
        $multiSourceMapper = $this->createMock(ShipmentMultiSource::class);
        $multiSourceMapper->expects($this->never())->method('getMagentoFormat');
        $resolver = new ShipmentMapperResolver(
            $this->inventoryMode($configuredMode),
            $singleSourceMapper,
            $multiSourceMapper
        );

        $this->assertSame($expected, $resolver->getMagentoFormat($record));
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
     * In multi source mode the multi source mapper handles the record and the single source mapper stays idle.
     */
    public function testItDelegatesToTheMultiSourceMapperInMultiMode(): void
    {
        $record = new Record();
        $expected = [['shipment' => 'multi_a'], ['shipment' => 'multi_b']];
        $multiSourceMapper = $this->createMock(ShipmentMultiSource::class);
        $multiSourceMapper->expects($this->once())
            ->method('getMagentoFormat')
            ->with($this->identicalTo($record))
            ->willReturn($expected);
        $singleSourceMapper = $this->createMock(ShipmentSingleSource::class);
        $singleSourceMapper->expects($this->never())->method('getMagentoFormat');
        $resolver = new ShipmentMapperResolver(
            $this->inventoryMode(InventoryMode::MODE_MULTI),
            $singleSourceMapper,
            $multiSourceMapper
        );

        $this->assertSame($expected, $resolver->getMagentoFormat($record));
    }

    /**
     * Flipping the configured mode swaps which mapper produces the result for the very same record.
     */
    public function testTheConfiguredModeDecidesWhichMapperProducesTheResult(): void
    {
        $record = new Record();
        $singleResult = [['shipment' => 'single']];
        $multiResult = [['shipment' => 'multi']];

        $this->assertSame(
            $singleResult,
            $this->resolveWith(InventoryMode::MODE_SINGLE, $record, $singleResult, $multiResult)
        );
        $this->assertSame(
            $multiResult,
            $this->resolveWith(InventoryMode::MODE_MULTI, $record, $singleResult, $multiResult)
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
        $multiSourceMapper = $this->createStub(ShipmentMultiSource::class);
        $multiSourceMapper->method('getMagentoFormat')->willReturn([]);
        $resolver = new ShipmentMapperResolver(
            new InventoryMode($scopeConfig),
            $this->createStub(ShipmentSingleSource::class),
            $multiSourceMapper
        );

        $this->assertSame([], $resolver->getMagentoFormat(new Record()));
    }

    /**
     * Runs the resolver once for the given mode with both mappers primed to return distinct results.
     *
     * @param string $configuredMode
     * @param Record $record
     * @param array $singleResult
     * @param array $multiResult
     * @return array
     */
    private function resolveWith(
        string $configuredMode,
        Record $record,
        array $singleResult,
        array $multiResult
    ): array {
        $singleSourceMapper = $this->createStub(ShipmentSingleSource::class);
        $singleSourceMapper->method('getMagentoFormat')->willReturn($singleResult);
        $multiSourceMapper = $this->createStub(ShipmentMultiSource::class);
        $multiSourceMapper->method('getMagentoFormat')->willReturn($multiResult);
        $resolver = new ShipmentMapperResolver(
            $this->inventoryMode($configuredMode),
            $singleSourceMapper,
            $multiSourceMapper
        );

        return $resolver->getMagentoFormat($record);
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
