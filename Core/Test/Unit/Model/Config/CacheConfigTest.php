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

use MageOS\NetSuiteConnector\Core\Model\Config\CacheConfig;
use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolver;
use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolverFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CacheConfigTest extends TestCase
{
    private const PATH_CACHE_SECONDS = 'mageos_netsuite/products/cache_seconds_for_lists_and_custom_records';

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
     * The single cache option must map its exact config path to the integer cast type.
     */
    public function testItPinsTheConfigPathAndCastType(): void
    {
        $this->assertSame(
            ['mageos_netsuite/products/cache_seconds_for_lists_and_custom_records' => 'int'],
            $this->createConfig([])->getOptionsMap()
        );
    }

    /**
     * The getter must ask the scope config for the exact literal path.
     */
    public function testItReadsTheCacheLifetimeFromItsExactPath(): void
    {
        $config = $this->createConfig([self::PATH_CACHE_SECONDS => '3600']);

        $this->assertSame(3600, $config->getCacheSecondsForListsAndCustomRecords());
        $this->assertContains(
            'mageos_netsuite/products/cache_seconds_for_lists_and_custom_records',
            $this->requestedPaths
        );
    }

    /**
     * The cache lifetime must be cast to an integer and default to zero when unset.
     */
    #[DataProvider('cacheLifetimeProvider')]
    public function testItCastsTheCacheLifetimeToInteger(mixed $stored, int $expected): void
    {
        $config = $this->createConfig([self::PATH_CACHE_SECONDS => $stored]);

        $this->assertSame($expected, $config->getCacheSecondsForListsAndCustomRecords());
    }

    /**
     * Data set of stored cache lifetimes and their integer result.
     *
     * @return array<string, array{mixed, int}>
     */
    public static function cacheLifetimeProvider(): array
    {
        return [
            'numeric string' => ['3600', 3600],
            'integer' => [3600, 3600],
            'zero disables caching' => ['0', 0],
            'missing value' => [null, 0],
            'non numeric value' => ['forever', 0],
        ];
    }

    /**
     * Build the subject with a scope config stub that answers only the given paths.
     *
     * @param array<string, mixed> $values
     * @return CacheConfig
     */
    private function createConfig(array $values): CacheConfig
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

        return new CacheConfig($factory);
    }
}
