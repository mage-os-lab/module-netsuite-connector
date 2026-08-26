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

namespace MageOS\NetSuiteConnector\Refund\Test\Unit\Model\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig;
use MageOS\NetSuiteConnector\Refund\Model\ConfigProvider\Permissions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the credit memo and cash refund import feature flags, including the connector master switch.
 */
class PermissionsTest extends TestCase
{
    private const GET_CREDIT_MEMO_PATH = 'mageos_netsuite/enable_disable_features/get_credit_memo';
    private const GET_CASH_REFUND_PATH = 'mageos_netsuite/enable_disable_features/get_cash_refund';

    /**
     * Each known feature code reports exactly what the scope configuration flag says while the connector is enabled.
     */
    #[DataProvider('knownFeatureFlagProvider')]
    public function testItReturnsTheConfiguredFlagForEachKnownFeatureCode(string $featureCode, bool $flag): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn($flag);
        $permissions = new Permissions($scopeConfig, $this->enabledConnectorConfig());

        $this->assertSame($flag, $permissions->isFeatureEnabled($featureCode));
    }

    /**
     * Provides every known feature code against both states of the scope configuration flag.
     *
     * @return array<string, array{0: string, 1: bool}>
     */
    public static function knownFeatureFlagProvider(): array
    {
        return [
            'credit memo enabled' => [Permissions::GET_CREDIT_MEMO, true],
            'credit memo disabled' => [Permissions::GET_CREDIT_MEMO, false],
            'cash refund enabled' => [Permissions::GET_CASH_REFUND, true],
            'cash refund disabled' => [Permissions::GET_CASH_REFUND, false],
        ];
    }

    /**
     * An unknown feature code is refused even when the connector is enabled and every flag is set.
     */
    #[DataProvider('unknownFeatureCodeProvider')]
    public function testItReturnsFalseForAnUnknownFeatureCode(string $featureCode): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn(true);
        $permissions = new Permissions($scopeConfig, $this->enabledConnectorConfig());

        $this->assertFalse($permissions->isFeatureEnabled($featureCode));
    }

    /**
     * Provides feature codes this module does not own.
     *
     * @return array<string, array{0: string}>
     */
    public static function unknownFeatureCodeProvider(): array
    {
        return [
            'empty code' => [''],
            'code owned by another module' => ['get_cash_sales'],
            'singular near miss' => ['get_credit_memos'],
            'full path instead of a code' => [self::GET_CREDIT_MEMO_PATH],
        ];
    }

    /**
     * Each known feature code is looked up under the shared feature path with that code appended.
     */
    #[DataProvider('featurePathProvider')]
    public function testItQueriesTheExactConfigurationPath(string $featureCode, string $expectedPath): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with($expectedPath)
            ->willReturn(true);
        $permissions = new Permissions($scopeConfig, $this->enabledConnectorConfig());

        $this->assertTrue($permissions->isFeatureEnabled($featureCode));
    }

    /**
     * Maps every known feature code to the full configuration path it must query.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function featurePathProvider(): array
    {
        return [
            'credit memo' => [Permissions::GET_CREDIT_MEMO, self::GET_CREDIT_MEMO_PATH],
            'cash refund' => [Permissions::GET_CASH_REFUND, self::GET_CASH_REFUND_PATH],
        ];
    }

    /**
     * Lists every known feature code on its own.
     *
     * @return array<string, array{0: string}>
     */
    public static function knownFeatureCodeProvider(): array
    {
        return [
            'credit memo' => [Permissions::GET_CREDIT_MEMO],
            'cash refund' => [Permissions::GET_CASH_REFUND],
        ];
    }

    /**
     * A disabled connector reports every known feature as disabled without reading the feature flag.
     */
    #[DataProvider('knownFeatureCodeProvider')]
    public function testItReportsEveryFeatureDisabledWhenTheConnectorIsDisabled(string $featureCode): void
    {
        $connectorConfig = $this->createStub(ConnectorConfig::class);
        $connectorConfig->method('isEnabled')->willReturn(false);
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->never())->method('isSetFlag');
        $permissions = new Permissions($scopeConfig, $connectorConfig);

        $this->assertFalse($permissions->isFeatureEnabled($featureCode));
    }

    /**
     * An unknown feature code never reaches the scope configuration at all.
     */
    public function testItDoesNotTouchScopeConfigForAnUnknownFeatureCode(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->never())->method('isSetFlag');
        $permissions = new Permissions($scopeConfig, $this->enabledConnectorConfig());

        $this->assertFalse($permissions->isFeatureEnabled('not_a_refund_feature'));
    }

    /**
     * Builds a connector configuration stub whose master switch is on.
     *
     * @return ConnectorConfig
     */
    private function enabledConnectorConfig(): ConnectorConfig
    {
        $connectorConfig = $this->createStub(ConnectorConfig::class);
        $connectorConfig->method('isEnabled')->willReturn(true);

        return $connectorConfig;
    }
}
