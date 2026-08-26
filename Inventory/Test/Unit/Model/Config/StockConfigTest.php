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

namespace MageOS\NetSuiteConnector\Inventory\Test\Unit\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolver;
use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolverFactory;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\LastUpdateManager;
use MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StockConfigTest extends TestCase
{
    private const PATH_UPDATE_EVERY_N_MINUTES = 'mageos_netsuite/stock/update_stocks_every_n_minutes';

    /**
     * Config paths requested from the scope config during the test.
     *
     * @var string[]
     */
    private array $requestedPaths = [];

    /**
     * Reset the recorded scope config requests before every test.
     */
    protected function setUp(): void
    {
        $this->requestedPaths = [];
    }

    /**
     * The publicly exposed constants must keep their exact literal values.
     */
    public function testItPinsThePublicConstants(): void
    {
        $this->assertSame('last_stock_update_date', StockConfig::FLAG_CODE);
        $this->assertSame('connection_stock', StockConfig::CONNECTION_SUBPATH);
    }

    /**
     * Every stock option must map its exact config path to its exact cast type.
     */
    public function testItPinsEveryConfigPathAndCastType(): void
    {
        $expected = [
            'mageos_netsuite/stock/stock_location' => 'int',
            'mageos_netsuite/stock/custom_search_id' => 'string',
            'mageos_netsuite/stock/custom_search_page_size' => 'int',
            'mageos_netsuite/stock/qty_field_name' => 'string',
            'mageos_netsuite/stock/qty_field_type' => 'string',
            'mageos_netsuite/stock/stock_stored_at_location_level' => 'bool',
            'mageos_netsuite/stock/change_stock_status_under_zero' => 'bool',
            'mageos_netsuite/stock/update_stocks_every_n_minutes' => 'int',
            'mageos_netsuite/connection_stock/same' => 'int',
        ];

        $actual = $this->createConfig([])->getOptionsMap();
        ksort($expected);
        ksort($actual);

        $this->assertSame($expected, $actual);
    }

    /**
     * Every getter must ask the scope config for its exact literal path and return the cast value.
     */
    #[DataProvider('getterPathProvider')]
    public function testItReadsEachGetterFromItsExactPath(
        string $method,
        string $expectedPath,
        mixed $stored,
        mixed $expected
    ): void {
        $config = $this->createConfig([$expectedPath => $stored]);

        $this->assertSame($expected, $config->{$method}());
        $this->assertContains($expectedPath, $this->requestedPaths);
    }

    /**
     * Data set of getter name, exact config path, stored value and expected cast result.
     *
     * @return array<string, array{string, string, mixed, mixed}>
     */
    public static function getterPathProvider(): array
    {
        return [
            'stock location' => [
                'getStockLocation',
                'mageos_netsuite/stock/stock_location',
                '7',
                7,
            ],
            'custom search id' => [
                'getCustomSearchId',
                'mageos_netsuite/stock/custom_search_id',
                'customsearch_stock',
                'customsearch_stock',
            ],
            'custom search page size' => [
                'getCustomSearchPageSize',
                'mageos_netsuite/stock/custom_search_page_size',
                '500',
                500,
            ],
            'qty field name' => [
                'getQtyFieldName',
                'mageos_netsuite/stock/qty_field_name',
                'quantityOnHand',
                'quantityOnHand',
            ],
            'qty field type' => [
                'getQtyFieldType',
                'mageos_netsuite/stock/qty_field_type',
                'standard',
                'standard',
            ],
            'stock stored at location level' => [
                'getStockStoredAtLocationLevel',
                'mageos_netsuite/stock/stock_stored_at_location_level',
                '1',
                true,
            ],
            'change stock status under zero' => [
                'getChangeStockStatusUnderZero',
                'mageos_netsuite/stock/change_stock_status_under_zero',
                '1',
                true,
            ],
            'update stocks every n minutes' => [
                'getUpdateStocksEveryNMinutes',
                'mageos_netsuite/stock/update_stocks_every_n_minutes',
                '360',
                360,
            ],
            'dedicated stock connection flag' => [
                'getSame',
                'mageos_netsuite/connection_stock/same',
                '1',
                1,
            ],
        ];
    }

    /**
     * Every getter must fall back to the zero value of its cast type when the config is empty.
     */
    #[DataProvider('emptyConfigDefaultProvider')]
    public function testItReturnsTheCastDefaultWhenConfigIsEmpty(string $method, mixed $expected): void
    {
        $config = $this->createConfig([]);

        $this->assertSame($expected, $config->{$method}());
    }

    /**
     * Data set of getter name and the value returned when nothing is configured.
     *
     * @return array<string, array{string, mixed}>
     */
    public static function emptyConfigDefaultProvider(): array
    {
        return [
            'stock location' => ['getStockLocation', 0],
            'custom search id' => ['getCustomSearchId', ''],
            'custom search page size' => ['getCustomSearchPageSize', 0],
            'qty field name' => ['getQtyFieldName', ''],
            'qty field type' => ['getQtyFieldType', ''],
            'stock stored at location level' => ['getStockStoredAtLocationLevel', false],
            'change stock status under zero' => ['getChangeStockStatusUnderZero', false],
            'update stocks every n minutes' => ['getUpdateStocksEveryNMinutes', 0],
            'dedicated stock connection flag' => ['getSame', 0],
        ];
    }

    /**
     * The class docblock advertises a standard quantity field getter that has no option behind it,
     * so calling it is rejected rather than silently returning an empty string.
     */
    public function testItRejectsTheUndeclaredStandardQtyFieldGetter(): void
    {
        $config = $this->createConfig([]);

        $this->expectException(\InvalidArgumentException::class);
        $config->getQtyFieldNameStandard();
    }

    /**
     * The stock import always runs while no last update flag has been stored.
     */
    public function testItAlwaysRunsWhenNoLastUpdateDateIsStored(): void
    {
        $config = $this->createConfig([self::PATH_UPDATE_EVERY_N_MINUTES => '360'], null);

        $this->assertTrue($config->shouldRun('2026-01-22 12:00:00'));
    }

    /**
     * The stock import always runs while the interval is not configured.
     */
    public function testItAlwaysRunsWhenTheIntervalIsZero(): void
    {
        $config = $this->createConfig([self::PATH_UPDATE_EVERY_N_MINUTES => '0'], '2026-01-22 11:59:00');

        $this->assertTrue($config->shouldRun('2026-01-22 12:00:00'));
    }

    /**
     * The stock import waits until the configured number of minutes has elapsed.
     */
    public function testItWaitsUntilTheIntervalHasElapsed(): void
    {
        $config = $this->createConfig([self::PATH_UPDATE_EVERY_N_MINUTES => '60'], '2026-01-22 11:30:00');

        $this->assertFalse($config->shouldRun('2026-01-22 12:00:00'));
    }

    /**
     * The stock import runs again once the configured number of minutes has elapsed.
     */
    public function testItRunsOnceTheIntervalHasElapsed(): void
    {
        $config = $this->createConfig([self::PATH_UPDATE_EVERY_N_MINUTES => '60'], '2026-01-22 10:30:00');

        $this->assertTrue($config->shouldRun('2026-01-22 12:00:00'));
    }

    /**
     * The interval boundary itself counts as elapsed.
     */
    public function testItRunsExactlyOnTheIntervalBoundary(): void
    {
        $config = $this->createConfig([self::PATH_UPDATE_EVERY_N_MINUTES => '60'], '2026-01-22 11:00:00');

        $this->assertTrue($config->shouldRun('2026-01-22 12:00:00'));
    }

    /**
     * The last update flag is looked up under the exact flag code the class publishes.
     */
    public function testItLooksUpTheLastUpdateDateByItsFlagCode(): void
    {
        $lastUpdateManager = $this->createMock(LastUpdateManager::class);
        $lastUpdateManager->expects($this->once())
            ->method('getLastUpdateDate')
            ->with('last_stock_update_date')
            ->willReturn('2026-01-22 10:30:00');

        $config = $this->buildConfig([self::PATH_UPDATE_EVERY_N_MINUTES => '60'], $lastUpdateManager);

        $this->assertTrue($config->shouldRun('2026-01-22 12:00:00'));
    }

    /**
     * Build the subject with a scope config stub and a stubbed last update date.
     *
     * @param array<string, mixed> $values
     * @param string|null $lastUpdateDate
     * @return StockConfig
     */
    private function createConfig(array $values, ?string $lastUpdateDate = null): StockConfig
    {
        $lastUpdateManager = $this->createStub(LastUpdateManager::class);
        $lastUpdateManager->method('getLastUpdateDate')->willReturn($lastUpdateDate);

        return $this->buildConfig($values, $lastUpdateManager);
    }

    /**
     * Build the subject around a prepared last update manager double.
     *
     * @param array<string, mixed> $values
     * @param LastUpdateManager $lastUpdateManager
     * @return StockConfig
     */
    private function buildConfig(array $values, LastUpdateManager $lastUpdateManager): StockConfig
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function ($path) use ($values) {
                $this->requestedPaths[] = (string)$path;
                return $values[$path] ?? null;
            }
        );

        $serializer = $this->createStub(SerializerInterface::class);
        $factory = $this->createStub(ConfigurationResolverFactory::class);
        $factory->method('create')->willReturnCallback(
            static fn (array $data): ConfigurationResolver => new ConfigurationResolver(
                $scopeConfig,
                $serializer,
                $data['optionsMap'],
                $data['cacheEnabled']
            )
        );

        return new StockConfig($lastUpdateManager, $factory);
    }
}
