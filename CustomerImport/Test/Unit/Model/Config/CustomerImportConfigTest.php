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

namespace MageOS\NetSuiteConnector\CustomerImport\Test\Unit\Model\Config;

use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolver;
use MageOS\NetSuiteConnector\Core\Model\Config\ConfigurationResolverFactory;
use MageOS\NetSuiteConnector\CustomerImport\Model\Config\CustomerImportConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CustomerImportConfigTest extends TestCase
{
    private const PATH_REQUIRED_ADDRESS_FIELDS = 'mageos_netsuite/customers_import/required_address_fields';

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
     * Every customer import option must map its exact config path to its exact cast type.
     */
    public function testItPinsEveryConfigPathAndCastType(): void
    {
        $expected = [
            'mageos_netsuite/customers_import/required_address_fields' => 'csv',
            'mageos_netsuite/customers_import/default_store_id' => 'int',
            'mageos_netsuite/customers_import/default_customer_group' => 'int',
            'mageos_netsuite/customers_import/default_address_city' => 'string',
            'mageos_netsuite/customers_import/default_address_phone' => 'string',
            'mageos_netsuite/customers_import/default_address_street' => 'string',
            'mageos_netsuite/customers_import/default_address_zip' => 'string',
            'mageos_netsuite/customers_import/default_address_country' => 'string',
            'mageos_netsuite/customers_import/login_message' => 'bool',
            'mageos_netsuite/customers_import/registration_message' => 'bool',
            'mageos_netsuite/customers_import/is_importable_field_id' => 'string',
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
            'default store id' => [
                'getDefaultStoreId',
                'mageos_netsuite/customers_import/default_store_id',
                '1',
                1,
            ],
            'default customer group' => [
                'getDefaultCustomerGroup',
                'mageos_netsuite/customers_import/default_customer_group',
                '3',
                3,
            ],
            'default address city' => [
                'getDefaultAddressCity',
                'mageos_netsuite/customers_import/default_address_city',
                'City Not Set',
                'City Not Set',
            ],
            'default address phone' => [
                'getDefaultAddressPhone',
                'mageos_netsuite/customers_import/default_address_phone',
                '123456789',
                '123456789',
            ],
            'default address street' => [
                'getDefaultAddressStreet',
                'mageos_netsuite/customers_import/default_address_street',
                'Street Not Set',
                'Street Not Set',
            ],
            'default address zip' => [
                'getDefaultAddressZip',
                'mageos_netsuite/customers_import/default_address_zip',
                '12000',
                '12000',
            ],
            'default address country' => [
                'getDefaultAddressCountry',
                'mageos_netsuite/customers_import/default_address_country',
                'US',
                'US',
            ],
            'login message' => [
                'getLoginMessage',
                'mageos_netsuite/customers_import/login_message',
                '1',
                true,
            ],
            'registration message' => [
                'getRegistrationMessage',
                'mageos_netsuite/customers_import/registration_message',
                '1',
                true,
            ],
            'is importable field id' => [
                'getIsImportableFieldId',
                'mageos_netsuite/customers_import/is_importable_field_id',
                'custentity_is_importable',
                'custentity_is_importable',
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
            'required address fields' => ['getRequiredAddressFields', []],
            'default store id' => ['getDefaultStoreId', 0],
            'default customer group' => ['getDefaultCustomerGroup', 0],
            'default address city' => ['getDefaultAddressCity', ''],
            'default address phone' => ['getDefaultAddressPhone', ''],
            'default address street' => ['getDefaultAddressStreet', ''],
            'default address zip' => ['getDefaultAddressZip', ''],
            'default address country' => ['getDefaultAddressCountry', ''],
            'login message' => ['getLoginMessage', false],
            'registration message' => ['getRegistrationMessage', false],
            'is importable field id' => ['getIsImportableFieldId', ''],
        ];
    }

    /**
     * The required address fields are read from their exact path and split on commas.
     */
    public function testItSplitsTheRequiredAddressFieldsOnCommas(): void
    {
        $config = $this->createConfig([self::PATH_REQUIRED_ADDRESS_FIELDS => 'phone,addr1,zip']);

        $this->assertSame(['phone', 'addr1', 'zip'], $config->getRequiredAddressFields());
        $this->assertContains(
            'mageos_netsuite/customers_import/required_address_fields',
            $this->requestedPaths
        );
    }

    /**
     * Whitespace around each required address field is trimmed away.
     */
    public function testItTrimsWhitespaceAroundEachRequiredAddressField(): void
    {
        $config = $this->createConfig([self::PATH_REQUIRED_ADDRESS_FIELDS => ' phone , addr1 , zip ']);

        $this->assertSame(['phone', 'addr1', 'zip'], $config->getRequiredAddressFields());
    }

    /**
     * Empty entries are dropped from the required address fields while the surviving keys keep their gaps.
     */
    public function testItDropsEmptyRequiredAddressFieldsAndLeavesKeyGaps(): void
    {
        $config = $this->createConfig([self::PATH_REQUIRED_ADDRESS_FIELDS => 'phone,,zip']);

        $this->assertSame([0 => 'phone', 2 => 'zip'], $config->getRequiredAddressFields());
    }

    /**
     * A single required address field is returned as a one element list.
     */
    public function testItReturnsASingleRequiredAddressFieldAsAList(): void
    {
        $config = $this->createConfig([self::PATH_REQUIRED_ADDRESS_FIELDS => 'phone']);

        $this->assertSame(['phone'], $config->getRequiredAddressFields());
    }

    /**
     * Build the subject with a scope config stub that answers only the given paths.
     *
     * @param array<string, mixed> $values
     * @return CustomerImportConfig
     */
    private function createConfig(array $values): CustomerImportConfig
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

        return new CustomerImportConfig($factory);
    }
}
