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

namespace MageOS\NetSuiteConnector\Shipment\Test\Unit\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolver;
use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolverFactory;
use MageOS\NetSuiteConnector\Shipment\Model\Config\ShippingConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ShippingConfigTest extends TestCase
{
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
     * The publicly exposed path constants must keep their exact literal values
     *
     * The default shipping id path and the admin field in etc/adminhtml/system.xml were spelled
     * differently, nesuite_ against netsuite_, so the reader never saw a configured value. Both
     * now use the correct spelling.
     */
    public function testItPinsThePublicPathConstants(): void
    {
        $this->assertSame(
            'mageos_netsuite/shipping_methods/send_tracking_information_on_import',
            ShippingConfig::SEND_TRACKING_INFO_ON_IMPORT
        );
        $this->assertSame(
            'mageos_netsuite/shipping_methods/netsuite_mapping',
            ShippingConfig::NETSUITE_MAPPING
        );
        $this->assertSame(
            'mageos_netsuite/shipping_methods/netsuite_default_shipping_id',
            ShippingConfig::NETSUITE_DEFAULT_SHIPPING_ID
        );
        $this->assertSame(
            'mageos_netsuite/shipping_methods/default_tracking_code_carrier',
            ShippingConfig::DEFAULT_TRACKING_CODE_CARRIER
        );
        $this->assertSame(
            'mageos_netsuite/shipping_methods/tracking_mapping',
            ShippingConfig::TRACKING_MAPPING
        );
    }

    /**
     * Every shipping option must map its exact config path to its exact cast type.
     */
    public function testItPinsEveryConfigPathAndCastType(): void
    {
        $expected = [
            'mageos_netsuite/shipping_methods/send_tracking_information_on_import' => 'bool',
            'mageos_netsuite/shipping_methods/netsuite_mapping' => 'json',
            'mageos_netsuite/shipping_methods/netsuite_default_shipping_id' => 'int',
            'mageos_netsuite/shipping_methods/default_tracking_code_carrier' => 'string',
            'mageos_netsuite/shipping_methods/tracking_mapping' => 'json',
        ];

        $actual = $this->createConfig([])->getOptionsMap();
        ksort($expected);
        ksort($actual);

        $this->assertSame($expected, $actual);
    }

    /**
     * Every scalar getter must ask the scope config for its exact literal path and return the cast value.
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
            'send tracking information on import' => [
                'getSendTrackingInformationOnImport',
                'mageos_netsuite/shipping_methods/send_tracking_information_on_import',
                '1',
                true,
            ],
            'netsuite default shipping id' => [
                'getNetsuiteDefaultShippingId',
                'mageos_netsuite/shipping_methods/netsuite_default_shipping_id',
                '2',
                2,
            ],
            'default tracking code carrier' => [
                'getDefaultTrackingCodeCarrier',
                'mageos_netsuite/shipping_methods/default_tracking_code_carrier',
                'custom',
                'custom',
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
            'send tracking information on import' => ['getSendTrackingInformationOnImport', false],
            'netsuite default shipping id' => ['getNetsuiteDefaultShippingId', 0],
            'default tracking code carrier' => ['getDefaultTrackingCodeCarrier', ''],
        ];
    }

    /**
     * A missing json option resolves to null rather than an empty array.
     */
    #[DataProvider('jsonGetterProvider')]
    public function testItReturnsNullForAnUnsetJsonOption(string $method): void
    {
        $config = $this->createConfig([]);

        $this->assertNull($config->{$method}());
    }

    /**
     * Data set of the getters backed by a json option.
     *
     * @return array<string, array{string}>
     */
    public static function jsonGetterProvider(): array
    {
        return [
            'netsuite mapping' => ['getNetsuiteMapping'],
            'tracking mapping' => ['getTrackingMapping'],
        ];
    }

    /**
     * The NetSuite mapping is read from its exact path and handed to the serializer.
     */
    public function testItDeserialisesTheNetsuiteMappingFromItsExactPath(): void
    {
        $stored = '{"row_1":{"magento_shipping":"flatrate_flatrate","netsuite_shipping":"1"}}';
        $config = $this->createConfig(['mageos_netsuite/shipping_methods/netsuite_mapping' => $stored]);

        $this->assertSame(
            ['row_1' => ['magento_shipping' => 'flatrate_flatrate', 'netsuite_shipping' => '1']],
            $config->getNetsuiteMapping()
        );
        $this->assertContains('mageos_netsuite/shipping_methods/netsuite_mapping', $this->requestedPaths);
    }

    /**
     * The tracking mapping is read from its exact path and handed to the serializer.
     */
    public function testItDeserialisesTheTrackingMappingFromItsExactPath(): void
    {
        $stored = '{"row_1":{"magento_carrier":"ups","netsuite_carrier":"UPS"}}';
        $config = $this->createConfig(['mageos_netsuite/shipping_methods/tracking_mapping' => $stored]);

        $this->assertSame(
            ['row_1' => ['magento_carrier' => 'ups', 'netsuite_carrier' => 'UPS']],
            $config->getTrackingMapping()
        );
        $this->assertContains('mageos_netsuite/shipping_methods/tracking_mapping', $this->requestedPaths);
    }

    /**
     * A json option that cannot be deserialised degrades to an empty array instead of raising.
     */
    public function testItReturnsAnEmptyArrayWhenTheStoredJsonIsInvalid(): void
    {
        $config = $this->createConfig(['mageos_netsuite/shipping_methods/tracking_mapping' => 'not-json']);

        $this->assertSame([], $config->getTrackingMapping());
    }

    /**
     * Build the subject with a scope config stub that answers only the given paths.
     *
     * @param array<string, mixed> $values
     * @return ShippingConfig
     */
    private function createConfig(array $values): ShippingConfig
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function ($path) use ($values) {
                $this->requestedPaths[] = (string)$path;
                return $values[$path] ?? null;
            }
        );

        $serializer = $this->createStub(SerializerInterface::class);
        $serializer->method('unserialize')->willReturnCallback(
            static function ($value) {
                $decoded = json_decode((string)$value, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \InvalidArgumentException('Unable to unserialize value.');
                }
                return $decoded;
            }
        );

        $factory = $this->createStub(ConfigurationResolverFactory::class);
        $factory->method('create')->willReturnCallback(
            static fn (array $data): ConfigurationResolver => new ConfigurationResolver(
                $scopeConfig,
                $serializer,
                $data['optionsMap'],
                $data['cacheEnabled']
            )
        );

        return new ShippingConfig($factory);
    }
}
