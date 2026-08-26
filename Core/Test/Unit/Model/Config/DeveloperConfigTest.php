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
use MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DeveloperConfigTest extends TestCase
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
     * Every developer option must map its exact config path to its exact cast type.
     */
    public function testItPinsEveryConfigPathAndCastType(): void
    {
        $expected = [
            'mageos_netsuite/developer/enable_api_log' => 'int',
            'mageos_netsuite/developer/errorlog_lifetime' => 'int',
            'mageos_netsuite/developer/clean_api_call_log_after' => 'int',
            'mageos_netsuite/developer/export_queue_threshold' => 'int',
            'mageos_netsuite/developer/import_queue_threshold' => 'int',
            'mageos_netsuite/developer/import_record_limit' => 'int',
            'mageos_netsuite/developer/mail_errors' => 'bool',
            'mageos_netsuite/developer/email' => 'string',
            'mageos_netsuite/developer/sender_email_identity' => 'string',
            'mageos_netsuite/developer/export_queue_threshold_email_template' => 'string',
            'mageos_netsuite/developer/import_queue_threshold_email_template' => 'string',
            'mageos_netsuite/developer/warn_if_export_not_run_after' => 'int',
            'mageos_netsuite/developer/warn_if_import_not_run_after' => 'int',
            'mageos_netsuite/developer/warn_if_stock_not_run_after' => 'int',
            'mageos_netsuite/developer/logger_level' => 'string',
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
            'enable api log' => [
                'getEnableApiLog',
                'mageos_netsuite/developer/enable_api_log',
                '1',
                1,
            ],
            'error log lifetime' => [
                'getErrorlogLifetime',
                'mageos_netsuite/developer/errorlog_lifetime',
                '100',
                100,
            ],
            'clean api call log after' => [
                'getCleanApiCallLogAfter',
                'mageos_netsuite/developer/clean_api_call_log_after',
                '30',
                30,
            ],
            'export queue threshold' => [
                'getExportQueueThreshold',
                'mageos_netsuite/developer/export_queue_threshold',
                '100',
                100,
            ],
            'import queue threshold' => [
                'getImportQueueThreshold',
                'mageos_netsuite/developer/import_queue_threshold',
                '100',
                100,
            ],
            'import record limit' => [
                'getImportRecordLimit',
                'mageos_netsuite/developer/import_record_limit',
                '300',
                300,
            ],
            'mail errors' => [
                'getMailErrors',
                'mageos_netsuite/developer/mail_errors',
                '1',
                true,
            ],
            'email' => [
                'getEmail',
                'mageos_netsuite/developer/email',
                'ops@example.com',
                'ops@example.com',
            ],
            'sender email identity' => [
                'getSenderEmailIdentity',
                'mageos_netsuite/developer/sender_email_identity',
                'general',
                'general',
            ],
            'export queue threshold email template' => [
                'getExportQueueThresholdEmailTemplate',
                'mageos_netsuite/developer/export_queue_threshold_email_template',
                'mageos_netsuite_developer_export_queue_threshold_email_template',
                'mageos_netsuite_developer_export_queue_threshold_email_template',
            ],
            'import queue threshold email template' => [
                'getImportQueueThresholdEmailTemplate',
                'mageos_netsuite/developer/import_queue_threshold_email_template',
                'mageos_netsuite_developer_import_queue_threshold_email_template',
                'mageos_netsuite_developer_import_queue_threshold_email_template',
            ],
            'warn if export not run after' => [
                'getWarnIfExportNotRunAfter',
                'mageos_netsuite/developer/warn_if_export_not_run_after',
                '6',
                6,
            ],
            'warn if import not run after' => [
                'getWarnIfImportNotRunAfter',
                'mageos_netsuite/developer/warn_if_import_not_run_after',
                '6',
                6,
            ],
            'warn if stock not run after' => [
                'getWarnIfStockNotRunAfter',
                'mageos_netsuite/developer/warn_if_stock_not_run_after',
                '6',
                6,
            ],
            'logger level' => [
                'getLoggerLevel',
                'mageos_netsuite/developer/logger_level',
                'debug',
                'debug',
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
            'enable api log' => ['getEnableApiLog', 0],
            'error log lifetime' => ['getErrorlogLifetime', 0],
            'clean api call log after' => ['getCleanApiCallLogAfter', 0],
            'export queue threshold' => ['getExportQueueThreshold', 0],
            'import queue threshold' => ['getImportQueueThreshold', 0],
            'import record limit' => ['getImportRecordLimit', 0],
            'mail errors' => ['getMailErrors', false],
            'email' => ['getEmail', ''],
            'sender email identity' => ['getSenderEmailIdentity', ''],
            'export queue threshold email template' => ['getExportQueueThresholdEmailTemplate', ''],
            'import queue threshold email template' => ['getImportQueueThresholdEmailTemplate', ''],
            'warn if export not run after' => ['getWarnIfExportNotRunAfter', 0],
            'warn if import not run after' => ['getWarnIfImportNotRunAfter', 0],
            'warn if stock not run after' => ['getWarnIfStockNotRunAfter', 0],
            'logger level' => ['getLoggerLevel', ''],
        ];
    }

    /**
     * The api log flag is an integer option, so any truthy string collapses to one.
     */
    public function testItCastsTheApiLogFlagToInteger(): void
    {
        $config = $this->createConfig(['mageos_netsuite/developer/enable_api_log' => '2']);

        $this->assertSame(2, $config->getEnableApiLog());
    }

    /**
     * Build the subject with a scope config stub that answers only the given paths.
     *
     * @param array<string, mixed> $values
     * @return DeveloperConfig
     */
    private function createConfig(array $values): DeveloperConfig
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

        return new DeveloperConfig($factory);
    }
}
