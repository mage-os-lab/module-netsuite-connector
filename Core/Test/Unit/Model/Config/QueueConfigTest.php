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

namespace MageOS\NetSuiteConnector\Core\Test\Unit\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolver;
use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolverFactory;
use MageOS\NetSuiteConnector\Core\Model\Config\QueueConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class QueueConfigTest extends TestCase
{
    /**
     * Scope config requests recorded as path, scope type and scope code.
     *
     * @var array<int, array{string, mixed, mixed}>
     */
    private array $requests = [];

    /**
     * Reset the recorded scope config requests before every test.
     */
    protected function setUp(): void
    {
        $this->requests = [];
    }

    /**
     * Every queue option must map its exact config path to its exact cast type.
     */
    public function testItPinsEveryConfigPathAndCastType(): void
    {
        $expected = [
            'mageos_netsuite/queue_processing/delete_existing_items_in_import_queue' => 'bool',
            'mageos_netsuite/queue_processing/delete_existing_items_in_import_queue_hours' => 'int',
            'mageos_netsuite/queue_processing/import_batch_size' => 'int',
            'mageos_netsuite/queue_processing/updated_from_minutes' => 'int',
            'mageos_netsuite/queue_processing/export_batch_size' => 'int',
            'mageos_netsuite/queue_processing/timeout' => 'int',
        ];

        $actual = $this->createConfig([])->getOptionsMap();
        ksort($expected);
        ksort($actual);

        $this->assertSame($expected, $actual);
    }

    /**
     * Every getter must ask the scope config for its exact literal path and return the stored value.
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
        $this->assertContains($expectedPath, array_column($this->requests, 0));
    }

    /**
     * Data set of getter name, exact config path, stored value and expected cast result.
     *
     * @return array<string, array{string, string, mixed, mixed}>
     */
    public static function getterPathProvider(): array
    {
        return [
            'delete existing items flag' => [
                'getDeleteExistingItemsInImportQueue',
                'mageos_netsuite/queue_processing/delete_existing_items_in_import_queue',
                '1',
                true,
            ],
            'delete existing items hours' => [
                'getDeleteExistingItemsInImportQueueHours',
                'mageos_netsuite/queue_processing/delete_existing_items_in_import_queue_hours',
                '24',
                24,
            ],
            'import batch size' => [
                'getImportBatchSize',
                'mageos_netsuite/queue_processing/import_batch_size',
                '100',
                100,
            ],
            'updated from minutes' => [
                'getUpdatedFromMinutes',
                'mageos_netsuite/queue_processing/updated_from_minutes',
                '15',
                15,
            ],
            'export batch size' => [
                'getExportBatchSize',
                'mageos_netsuite/queue_processing/export_batch_size',
                '250',
                250,
            ],
            'timeout' => [
                'getTimeout',
                'mageos_netsuite/queue_processing/timeout',
                '100',
                100,
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
            'delete existing items flag' => ['getDeleteExistingItemsInImportQueue', false],
            'delete existing items hours' => ['getDeleteExistingItemsInImportQueueHours', 0],
            'import batch size' => ['getImportBatchSize', 0],
            'updated from minutes' => ['getUpdatedFromMinutes', 0],
            'export batch size' => ['getExportBatchSize', 0],
            'timeout' => ['getTimeout', 0],
        ];
    }

    /**
     * An integer option stored as a non numeric string must be cast to zero rather than raise an error.
     */
    public function testItCastsNonNumericIntegerOptionsToZero(): void
    {
        $config = $this->createConfig(['mageos_netsuite/queue_processing/export_batch_size' => 'not-a-number']);

        $this->assertSame(0, $config->getExportBatchSize());
    }

    /**
     * A getter that does not exist in the options map must be rejected loudly.
     */
    public function testItRejectsAnUnknownGetter(): void
    {
        $config = $this->createConfig([]);

        $this->expectException(\InvalidArgumentException::class);
        $config->getSomethingThatDoesNotExist();
    }

    /**
     * Values are cached per path so repeated calls hit the scope config only once.
     */
    public function testItCachesEachResolvedPath(): void
    {
        $config = $this->createConfig(['mageos_netsuite/queue_processing/timeout' => '100']);
        $config->getTimeout();
        $config->getTimeout();
        $config->getTimeout();

        $this->assertCount(1, $this->requestsFor('mageos_netsuite/queue_processing/timeout'));
    }

    /**
     * Disabling the cache makes every call hit the scope config again.
     */
    public function testItRereadsEveryCallWhenTheCacheIsDisabled(): void
    {
        $config = $this->createConfig(['mageos_netsuite/queue_processing/timeout' => '100']);
        $config->setCacheEnabled(false);
        $config->getTimeout();
        $config->getTimeout();

        $this->assertCount(2, $this->requestsFor('mageos_netsuite/queue_processing/timeout'));
    }

    /**
     * Options are read from the default scope until a different scope is selected.
     */
    public function testItReadsFromTheDefaultScopeByDefault(): void
    {
        $config = $this->createConfig(['mageos_netsuite/queue_processing/timeout' => '100']);
        $config->getTimeout();

        $request = $this->requestsFor('mageos_netsuite/queue_processing/timeout')[0];

        $this->assertSame(ScopeConfigInterface::SCOPE_TYPE_DEFAULT, $request[1]);
        $this->assertNull($request[2]);
    }

    /**
     * Selecting a scope forwards that scope type and code to every later read.
     */
    public function testItForwardsTheSelectedScopeToTheScopeConfig(): void
    {
        $config = $this->createConfig(['mageos_netsuite/queue_processing/timeout' => '100']);
        $config->setScopeTypeAndCode('store', 'default');
        $config->getTimeout();

        $request = $this->requestsFor('mageos_netsuite/queue_processing/timeout')[0];

        $this->assertSame('store', $request[1]);
        $this->assertSame('default', $request[2]);
    }

    /**
     * Collect the recorded scope config requests for a single path.
     *
     * @param string $path
     * @return array<int, array{string, mixed, mixed}>
     */
    private function requestsFor(string $path): array
    {
        return array_values(
            array_filter($this->requests, static fn (array $request): bool => $request[0] === $path)
        );
    }

    /**
     * Build the subject with a scope config stub that answers only the given paths.
     *
     * @param array<string, mixed> $values
     * @return QueueConfig
     */
    private function createConfig(array $values): QueueConfig
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function ($path, $scopeType = null, $scopeCode = null) use ($values) {
                $this->requests[] = [(string)$path, $scopeType, $scopeCode];
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

        return new QueueConfig($factory);
    }
}
