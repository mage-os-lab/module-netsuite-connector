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

namespace MageOS\NetSuiteConnector\Order\Test\Unit\Model\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\NetSuiteConnector\Order\Model\ConfigProvider\Permissions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the order export and order change import feature flags.
 */
class PermissionsTest extends TestCase
{
    private const SEND_ORDERS_PATH = 'mageos_netsuite/enable_disable_features/send_orders';
    private const GET_ORDER_CHANGES_PATH = 'mageos_netsuite/enable_disable_features/get_order_changes';

    /**
     * Each known feature code reports exactly what the scope configuration flag says.
     */
    #[DataProvider('knownFeatureFlagProvider')]
    public function testItReturnsTheConfiguredFlagForEachKnownFeatureCode(string $featureCode, bool $flag): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn($flag);
        $permissions = new Permissions($scopeConfig);

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
            'send orders enabled' => [Permissions::SEND_ORDERS, true],
            'send orders disabled' => [Permissions::SEND_ORDERS, false],
            'get order changes enabled' => [Permissions::GET_ORDER_CHANGES, true],
            'get order changes disabled' => [Permissions::GET_ORDER_CHANGES, false],
        ];
    }

    /**
     * An unknown feature code is refused even when every configuration flag is set.
     */
    #[DataProvider('unknownFeatureCodeProvider')]
    public function testItReturnsFalseForAnUnknownFeatureCode(string $featureCode): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn(true);
        $permissions = new Permissions($scopeConfig);

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
            'code owned by another module' => ['send_invoices'],
            'singular near miss' => ['send_order'],
            'partial near miss' => ['get_order'],
            'full path instead of a code' => [self::SEND_ORDERS_PATH],
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
        $permissions = new Permissions($scopeConfig);

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
            'send orders' => [Permissions::SEND_ORDERS, self::SEND_ORDERS_PATH],
            'get order changes' => [Permissions::GET_ORDER_CHANGES, self::GET_ORDER_CHANGES_PATH],
        ];
    }

    /**
     * An unknown feature code never reaches the scope configuration at all.
     */
    public function testItDoesNotTouchScopeConfigForAnUnknownFeatureCode(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->never())->method('isSetFlag');
        $permissions = new Permissions($scopeConfig);

        $this->assertFalse($permissions->isFeatureEnabled('not_an_order_feature'));
    }
}
