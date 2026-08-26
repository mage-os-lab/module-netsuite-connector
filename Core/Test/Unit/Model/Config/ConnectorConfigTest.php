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

use MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ConnectorConfigTest extends TestCase
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
     * The publicly exposed path constants must keep their exact literal values.
     */
    public function testItPinsThePublicPathConstants(): void
    {
        $this->assertSame('mageos_netsuite/general/enabled', ConnectorConfig::PATH_ENABLED);
        $this->assertSame('mageos_netsuite/general/nsendpoint', ConnectorConfig::PATH_ENDPOINT);
    }

    /**
     * Every simple getter must ask the scope config for its exact literal path.
     */
    #[DataProvider('simpleGetterPathProvider')]
    public function testItReadsEachSimpleGetterFromItsExactPath(string $method, string $expectedPath): void
    {
        $config = $this->createConfig([$expectedPath => '1']);
        $config->{$method}();

        $this->assertContains($expectedPath, $this->requestedPaths);
    }

    /**
     * Data set of getter name and the exact config path it must read.
     *
     * @return array<string, array{string, string}>
     */
    public static function simpleGetterPathProvider(): array
    {
        return [
            'isEnabled' => ['isEnabled', 'mageos_netsuite/general/enabled'],
            'getHost' => ['getHost', 'mageos_netsuite/general/host'],
            'getEndpoint' => ['getEndpoint', 'mageos_netsuite/general/nsendpoint'],
            'getAccountId' => ['getAccountId', 'mageos_netsuite/general/account_id'],
            'getSoapRequestTimeout' => ['getSoapRequestTimeout', 'mageos_netsuite/general/soap_request_timeout'],
            'getNetsuiteBaseUrl' => ['getNetsuiteBaseUrl', 'mageos_netsuite/general/netsuite_base_url'],
        ];
    }

    /**
     * The enabled flag must be cast to a boolean.
     */
    #[DataProvider('enabledProvider')]
    public function testItCastsTheEnabledFlagToBoolean(mixed $stored, bool $expected): void
    {
        $config = $this->createConfig(['mageos_netsuite/general/enabled' => $stored]);

        $this->assertSame($expected, $config->isEnabled());
    }

    /**
     * Data set of stored enabled values and their boolean result.
     *
     * @return array<string, array{mixed, bool}>
     */
    public static function enabledProvider(): array
    {
        return [
            'string one' => ['1', true],
            'string zero' => ['0', false],
            'empty string' => ['', false],
            'missing value' => [null, false],
        ];
    }

    /**
     * The host must be returned without a trailing slash and as an empty string when unset.
     */
    #[DataProvider('hostProvider')]
    public function testItNormalisesTheHost(mixed $stored, string $expected): void
    {
        $config = $this->createConfig(['mageos_netsuite/general/host' => $stored]);

        $this->assertSame($expected, $config->getHost());
    }

    /**
     * Data set of stored host values and their normalised result.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function hostProvider(): array
    {
        return [
            'trailing slash trimmed' => [
                'https://acme.suitetalk.api.netsuite.com/',
                'https://acme.suitetalk.api.netsuite.com',
            ],
            'no trailing slash kept' => [
                'https://acme.suitetalk.api.netsuite.com',
                'https://acme.suitetalk.api.netsuite.com',
            ],
            'missing value becomes empty string' => [null, ''],
        ];
    }

    /**
     * The endpoint must be returned verbatim from its config path.
     */
    public function testItReturnsTheEndpointVerbatim(): void
    {
        $config = $this->createConfig(['mageos_netsuite/general/nsendpoint' => '2024_2']);

        $this->assertSame('2024_2', $config->getEndpoint());
    }

    /**
     * An unset endpoint returns an empty string rather than breaking the string return type
     *
     * The getter used to return the raw cached value, so an unconfigured endpoint threw a
     * TypeError. Its sibling getAccountId always cast, and getEndpoint now does the same.
     */
    public function testItReturnsAnEmptyStringWhenTheEndpointIsNotConfigured(): void
    {
        $config = $this->createConfig([]);

        $this->assertSame('', $config->getEndpoint());
    }

    /**
     * The account id must be cast to a string and default to an empty string.
     */
    #[DataProvider('accountIdProvider')]
    public function testItCastsTheAccountIdToString(mixed $stored, string $expected): void
    {
        $config = $this->createConfig(['mageos_netsuite/general/account_id' => $stored]);

        $this->assertSame($expected, $config->getAccountId());
    }

    /**
     * Data set of stored account id values and their string result.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function accountIdProvider(): array
    {
        return [
            'string account id' => ['TSTDRV1234567', 'TSTDRV1234567'],
            'numeric account id' => [1234567, '1234567'],
            'missing value' => [null, ''],
        ];
    }

    /**
     * The SOAP request timeout must be cast to an integer and default to zero.
     */
    #[DataProvider('soapTimeoutProvider')]
    public function testItCastsTheSoapRequestTimeoutToInteger(mixed $stored, int $expected): void
    {
        $config = $this->createConfig(['mageos_netsuite/general/soap_request_timeout' => $stored]);

        $this->assertSame($expected, $config->getSoapRequestTimeout());
    }

    /**
     * Data set of stored timeout values and their integer result.
     *
     * @return array<string, array{mixed, int}>
     */
    public static function soapTimeoutProvider(): array
    {
        return [
            'numeric string' => ['180', 180],
            'integer' => [180, 180],
            'missing value' => [null, 0],
        ];
    }

    /**
     * The NetSuite base url must always end with exactly one slash, or be empty when unset.
     */
    #[DataProvider('baseUrlProvider')]
    public function testItNormalisesTheNetsuiteBaseUrl(mixed $stored, string $expected): void
    {
        $config = $this->createConfig(['mageos_netsuite/general/netsuite_base_url' => $stored]);

        $this->assertSame($expected, $config->getNetsuiteBaseUrl());
    }

    /**
     * Data set of stored base url values and their normalised result.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function baseUrlProvider(): array
    {
        return [
            'slash appended' => ['https://1234567.app.netsuite.com', 'https://1234567.app.netsuite.com/'],
            'duplicate slashes collapsed' => [
                'https://1234567.app.netsuite.com//',
                'https://1234567.app.netsuite.com/',
            ],
            'missing value stays empty' => [null, ''],
            'empty string stays empty' => ['', ''],
        ];
    }

    /**
     * The retry count is a hardcoded constant and never reads the scope config.
     */
    public function testItReturnsAFixedRetryCount(): void
    {
        $config = $this->createConfig([]);

        $this->assertSame(3, $config->getRetriesCount());
        $this->assertSame([], $this->requestedPaths);
    }

    /**
     * The run mode subpath must be decided by the exact "same" flag path of that run mode.
     */
    #[DataProvider('runModeProvider')]
    public function testItResolvesTheSubpathForARunMode(
        string $runMode,
        string $sameFlagPath,
        mixed $sameFlagValue,
        string $expectedSubpath
    ): void {
        $config = $this->createConfig([$sameFlagPath => $sameFlagValue]);

        $this->assertSame($expectedSubpath, $config->getSystemConfigPathForRunMode($runMode));
        $this->assertContains($sameFlagPath, $this->requestedPaths);
    }

    /**
     * Data set of run modes, their "same" flag path and the resulting subpath.
     *
     * @return array<string, array{string, string, mixed, string}>
     */
    public static function runModeProvider(): array
    {
        return [
            'import with dedicated connection' => [
                'import',
                'mageos_netsuite/connection_import/same',
                '0',
                'connection_import',
            ],
            'import sharing the general connection' => [
                'import',
                'mageos_netsuite/connection_import/same',
                '1',
                'general',
            ],
            'import with unset flag falls back to general' => [
                'import',
                'mageos_netsuite/connection_import/same',
                null,
                'general',
            ],
            'export with dedicated connection' => [
                'export',
                'mageos_netsuite/connection_export/same',
                '0',
                'connection_export',
            ],
            'export sharing the general connection' => [
                'export',
                'mageos_netsuite/connection_export/same',
                '1',
                'general',
            ],
            'unknown run mode uses the general connection' => [
                'stock',
                'mageos_netsuite/general/same',
                '0',
                'general',
            ],
        ];
    }

    /**
     * The "same" flag is compared strictly against the string zero, so an integer zero shares the general connection.
     */
    public function testItComparesTheSameFlagStrictlyAgainstAString(): void
    {
        $config = $this->createConfig(['mageos_netsuite/connection_import/same' => 0]);

        $this->assertSame('general', $config->getSystemConfigPathForRunMode('import'));
    }

    /**
     * Each credential getter must read its exact path under the general connection subpath.
     */
    #[DataProvider('generalCredentialProvider')]
    public function testItReadsCredentialsFromTheGeneralSubpath(string $method, string $expectedPath): void
    {
        $config = $this->createConfig([
            'mageos_netsuite/connection_import/same' => '1',
            $expectedPath => 'secret-value',
        ]);

        $this->assertSame('secret-value', $config->{$method}('import'));
        $this->assertContains($expectedPath, $this->requestedPaths);
    }

    /**
     * Data set of credential getters and their general connection path.
     *
     * @return array<string, array{string, string}>
     */
    public static function generalCredentialProvider(): array
    {
        return [
            'consumer key' => ['getConsumerKey', 'mageos_netsuite/general/consumer_key'],
            'consumer secret' => ['getConsumerSecret', 'mageos_netsuite/general/consumer_secret'],
            'token id' => ['getTokenId', 'mageos_netsuite/general/token_id'],
            'token secret' => ['getTokenSecret', 'mageos_netsuite/general/token_secret'],
        ];
    }

    /**
     * Each credential getter must read its exact path under the dedicated import subpath.
     */
    #[DataProvider('importCredentialProvider')]
    public function testItReadsCredentialsFromTheDedicatedImportSubpath(string $method, string $expectedPath): void
    {
        $config = $this->createConfig([
            'mageos_netsuite/connection_import/same' => '0',
            $expectedPath => 'import-value',
        ]);

        $this->assertSame('import-value', $config->{$method}('import'));
        $this->assertContains($expectedPath, $this->requestedPaths);
    }

    /**
     * Data set of credential getters and their dedicated import connection path.
     *
     * @return array<string, array{string, string}>
     */
    public static function importCredentialProvider(): array
    {
        return [
            'consumer key' => ['getConsumerKey', 'mageos_netsuite/connection_import/consumer_key'],
            'consumer secret' => ['getConsumerSecret', 'mageos_netsuite/connection_import/consumer_secret'],
            'token id' => ['getTokenId', 'mageos_netsuite/connection_import/token_id'],
            'token secret' => ['getTokenSecret', 'mageos_netsuite/connection_import/token_secret'],
        ];
    }

    /**
     * Each credential getter must read its exact path under the dedicated export subpath.
     */
    #[DataProvider('exportCredentialProvider')]
    public function testItReadsCredentialsFromTheDedicatedExportSubpath(string $method, string $expectedPath): void
    {
        $config = $this->createConfig([
            'mageos_netsuite/connection_export/same' => '0',
            $expectedPath => 'export-value',
        ]);

        $this->assertSame('export-value', $config->{$method}('export'));
        $this->assertContains($expectedPath, $this->requestedPaths);
    }

    /**
     * Data set of credential getters and their dedicated export connection path.
     *
     * @return array<string, array{string, string}>
     */
    public static function exportCredentialProvider(): array
    {
        return [
            'consumer key' => ['getConsumerKey', 'mageos_netsuite/connection_export/consumer_key'],
            'consumer secret' => ['getConsumerSecret', 'mageos_netsuite/connection_export/consumer_secret'],
            'token id' => ['getTokenId', 'mageos_netsuite/connection_export/token_id'],
            'token secret' => ['getTokenSecret', 'mageos_netsuite/connection_export/token_secret'],
        ];
    }

    /**
     * A cached getter must hit the scope config only once for the same path.
     */
    public function testItCachesResolvedValuesPerPath(): void
    {
        $config = $this->createConfig(['mageos_netsuite/general/account_id' => 'TSTDRV1234567']);
        $config->getAccountId();
        $config->getAccountId();
        $config->getAccountId();

        $accountIdRequests = array_filter(
            $this->requestedPaths,
            static fn (string $path): bool => $path === 'mageos_netsuite/general/account_id'
        );

        $this->assertCount(1, $accountIdRequests);
    }

    /**
     * The enabled flag bypasses the cache and is read on every call.
     */
    public function testItDoesNotCacheTheEnabledFlag(): void
    {
        $config = $this->createConfig(['mageos_netsuite/general/enabled' => '1']);
        $config->isEnabled();
        $config->isEnabled();

        $enabledRequests = array_filter(
            $this->requestedPaths,
            static fn (string $path): bool => $path === 'mageos_netsuite/general/enabled'
        );

        $this->assertCount(2, $enabledRequests);
    }

    /**
     * Build the subject with a scope config stub that answers only the given paths.
     *
     * @param array<string, mixed> $values
     * @return ConnectorConfig
     */
    private function createConfig(array $values): ConnectorConfig
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function ($path) use ($values) {
                $this->requestedPaths[] = (string)$path;
                return $values[$path] ?? null;
            }
        );

        return new ConnectorConfig($scopeConfig);
    }
}
