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

namespace MageOS\NetSuiteConnector\Inventory\Multi\Test\Unit\Model\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode;
use MageOS\NetSuiteConnector\Inventory\Multi\Model\ConfigProvider\Permissions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the location import feature flag and the multi source inventory mode guard that gates it.
 */
class PermissionsTest extends TestCase
{
    private const IMPORT_LOCATION_PATH = 'mageos_netsuite/enable_disable_features/import_location';

    /**
     * In multi source mode the known feature code reports exactly what the scope configuration flag says.
     */
    #[DataProvider('flagProvider')]
    public function testItReturnsTheConfiguredFlagInMultiMode(bool $flag): void
    {
        $permissions = $this->createPermissions(InventoryMode::MODE_MULTI, $flag);

        $this->assertSame($flag, $permissions->isFeatureEnabled(Permissions::IMPORT));
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
     * In single source mode every feature is reported disabled no matter how the flag is set.
     */
    #[DataProvider('singleModeValueProvider')]
    public function testItReportsEveryFeatureDisabledOutsideMultiMode(string $configuredMode): void
    {
        $permissions = $this->createPermissions($configuredMode, true);

        $this->assertFalse($permissions->isFeatureEnabled(Permissions::IMPORT));
    }

    /**
     * Provides every configured value that does not select multi source mode.
     *
     * @return array<string, array{0: string}>
     */
    public static function singleModeValueProvider(): array
    {
        return [
            'explicit single mode' => [InventoryMode::MODE_SINGLE],
            'unset configuration' => [''],
            'unrecognised value' => ['something_else'],
        ];
    }

    /**
     * In single source mode the feature flag is never read at all.
     */
    public function testItDoesNotTouchTheFeatureFlagOutsideMultiMode(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(InventoryMode::MODE_SINGLE);
        $scopeConfig->expects($this->never())->method('isSetFlag');
        $permissions = new Permissions($scopeConfig, new InventoryMode($scopeConfig));

        $this->assertFalse($permissions->isFeatureEnabled(Permissions::IMPORT));
    }

    /**
     * In multi source mode the flag is read from the shared feature path with the feature code appended.
     */
    public function testItQueriesTheExactConfigurationPathInMultiMode(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(InventoryMode::MODE_MULTI);
        $scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(self::IMPORT_LOCATION_PATH)
            ->willReturn(true);
        $permissions = new Permissions($scopeConfig, new InventoryMode($scopeConfig));

        $this->assertTrue($permissions->isFeatureEnabled(Permissions::IMPORT));
    }

    /**
     * The inventory mode is read from the general inventory mode configuration path.
     */
    public function testItReadsTheModeFromTheInventoryModeConfigurationPath(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->atLeastOnce())
            ->method('getValue')
            ->with(InventoryMode::CONFIG_PATH)
            ->willReturn(InventoryMode::MODE_MULTI);
        $scopeConfig->method('isSetFlag')->willReturn(true);
        $permissions = new Permissions($scopeConfig, new InventoryMode($scopeConfig));

        $this->assertTrue($permissions->isFeatureEnabled(Permissions::IMPORT));
    }

    /**
     * Builds the subject under test with the given inventory mode and feature flag state.
     *
     * @param string $configuredMode
     * @param bool $flag
     * @return Permissions
     */
    private function createPermissions(string $configuredMode, bool $flag): Permissions
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn($configuredMode);
        $scopeConfig->method('isSetFlag')->willReturn($flag);

        return new Permissions($scopeConfig, new InventoryMode($scopeConfig));
    }
}
