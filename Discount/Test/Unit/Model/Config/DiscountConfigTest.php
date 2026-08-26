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

namespace MageOS\NetSuiteConnector\Discount\Test\Unit\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolver;
use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolverFactory;
use MageOS\NetSuiteConnector\Discount\Model\Config\DiscountConfig;
use MageOS\NetSuiteConnector\Discount\Model\Config\Source\LogicSwitcher;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DiscountConfigTest extends TestCase
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
     * Every discount option must map its exact config path to its exact cast type.
     */
    public function testItPinsEveryConfigPathAndCastType(): void
    {
        $expected = [
            'mageos_netsuite/orders/discount_item_id' => 'int',
            'mageos_netsuite/orders/disable_order_level_discount' => 'bool',
            'mageos_netsuite/orders/logic_switch' => 'string',
            'mageos_netsuite/orders/add_promotion_data' => 'bool',
            'mageos_netsuite/orders/order_skip_discount' => 'bool',
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
            'discount item id' => [
                'getDiscountItemId',
                'mageos_netsuite/orders/discount_item_id',
                '42',
                42,
            ],
            'disable order level discount' => [
                'getDisableOrderLevelDiscount',
                'mageos_netsuite/orders/disable_order_level_discount',
                '1',
                true,
            ],
            'logic switch' => [
                'getLogicSwitch',
                'mageos_netsuite/orders/logic_switch',
                'body',
                'body',
            ],
            'add promotion data' => [
                'getAddPromotionData',
                'mageos_netsuite/orders/add_promotion_data',
                '1',
                true,
            ],
            'order skip discount' => [
                'getOrderSkipDiscount',
                'mageos_netsuite/orders/order_skip_discount',
                '1',
                true,
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
            'discount item id' => ['getDiscountItemId', 0],
            'disable order level discount' => ['getDisableOrderLevelDiscount', false],
            'logic switch' => ['getLogicSwitch', ''],
            'add promotion data' => ['getAddPromotionData', false],
            'order skip discount' => ['getOrderSkipDiscount', false],
        ];
    }

    /**
     * The logic switch is resolved as a string even though the class docblock declares it a boolean,
     * so the value stored by the option source survives unchanged.
     */
    public function testItKeepsTheLogicSwitchValueAsAString(): void
    {
        $config = $this->createConfig(['mageos_netsuite/orders/logic_switch' => LogicSwitcher::BODY]);

        $this->assertSame('body', $config->getLogicSwitch());
    }

    /**
     * Build the subject with a scope config stub that answers only the given paths.
     *
     * @param array<string, mixed> $values
     * @return DiscountConfig
     */
    private function createConfig(array $values): DiscountConfig
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

        return new DiscountConfig($factory);
    }
}
