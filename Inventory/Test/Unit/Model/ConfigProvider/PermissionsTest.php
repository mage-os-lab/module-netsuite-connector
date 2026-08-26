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

namespace MageOS\NetSuiteConnector\Inventory\Test\Unit\Model\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig;
use MageOS\NetSuiteConnector\Inventory\Model\ConfigProvider\Permissions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the stock update feature flag and the connector master switch that gates it.
 */
class PermissionsTest extends TestCase
{
    private const GET_STOCK_UPDATES_PATH = 'mageos_netsuite/enable_disable_features/get_stock_updates';

    /**
     * The stock update flag reports exactly what the scope configuration says while the connector is enabled.
     */
    #[DataProvider('flagProvider')]
    public function testItReturnsTheConfiguredStockUpdateFlag(bool $flag): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn($flag);
        $permissions = new Permissions($scopeConfig, $this->enabledConnectorConfig());

        $this->assertSame($flag, $permissions->isFeatureEnabled(Permissions::GET_STOCK_UPDATES));
    }

    /**
     * Provides both states of the scope configuration flag.
     *
     * @return array<string, array{0: bool}>
     */
    public static function flagProvider(): array
    {
        return [
            'flag set' => [true],
            'flag not set' => [false],
        ];
    }

    /**
     * The flag is read from the full stock update path, and nothing else is read.
     */
    public function testItQueriesTheExactConfigurationPath(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(self::GET_STOCK_UPDATES_PATH)
            ->willReturn(true);
        $permissions = new Permissions($scopeConfig, $this->enabledConnectorConfig());

        $this->assertTrue($permissions->isFeatureEnabled(Permissions::GET_STOCK_UPDATES));
    }

    /**
     * A disabled connector reports the stock update feature as disabled without reading the feature flag.
     */
    public function testItReportsTheFeatureDisabledWhenTheConnectorIsDisabled(): void
    {
        $connectorConfig = $this->createStub(ConnectorConfig::class);
        $connectorConfig->method('isEnabled')->willReturn(false);
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->never())->method('isSetFlag');
        $permissions = new Permissions($scopeConfig, $connectorConfig);

        $this->assertFalse($permissions->isFeatureEnabled(Permissions::GET_STOCK_UPDATES));
    }

    /**
     * The feature code argument is ignored: this provider has no unknown code guard and always resolves the
     * stock update path. Change this test only together with the production guard it documents.
     */
    #[DataProvider('arbitraryFeatureCodeProvider')]
    public function testItIgnoresTheFeatureCodeArgumentAndAlwaysResolvesTheStockUpdatePath(string $featureCode): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(self::GET_STOCK_UPDATES_PATH)
            ->willReturn(true);
        $permissions = new Permissions($scopeConfig, $this->enabledConnectorConfig());

        $this->assertTrue($permissions->isFeatureEnabled($featureCode));
    }

    /**
     * Provides feature codes that are not the stock update code.
     *
     * @return array<string, array{0: string}>
     */
    public static function arbitraryFeatureCodeProvider(): array
    {
        return [
            'empty code' => [''],
            'code owned by another module' => ['send_orders'],
            'bare stock update code' => ['get_stock_updates'],
        ];
    }

    /**
     * The default argument keeps the call site free to omit a feature code entirely.
     */
    public function testItAcceptsACallWithoutAFeatureCode(): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn(true);
        $permissions = new Permissions($scopeConfig, $this->enabledConnectorConfig());

        $this->assertTrue($permissions->isFeatureEnabled());
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
