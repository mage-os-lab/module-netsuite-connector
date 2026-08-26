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

namespace MageOS\NetSuiteConnector\Invoice\Test\Unit\Model\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\NetSuiteConnector\Invoice\Model\ConfigProvider\Permissions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the invoice export and cash sale import feature flags.
 */
class PermissionsTest extends TestCase
{
    private const SEND_INVOICES_PATH = 'mageos_netsuite/enable_disable_features/send_invoices';
    private const GET_CASH_SALES_PATH = 'mageos_netsuite/enable_disable_features/get_cash_sales';

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
            'send invoices enabled' => [Permissions::SEND_INVOICES, true],
            'send invoices disabled' => [Permissions::SEND_INVOICES, false],
            'get cash sales enabled' => [Permissions::GET_CASH_SALES, true],
            'get cash sales disabled' => [Permissions::GET_CASH_SALES, false],
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
            'code owned by another module' => ['send_orders'],
            'refund code that looks similar' => ['get_cash_refund'],
            'singular near miss' => ['send_invoice'],
            'full path instead of a code' => [self::SEND_INVOICES_PATH],
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
            'send invoices' => [Permissions::SEND_INVOICES, self::SEND_INVOICES_PATH],
            'get cash sales' => [Permissions::GET_CASH_SALES, self::GET_CASH_SALES_PATH],
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

        $this->assertFalse($permissions->isFeatureEnabled('not_an_invoice_feature'));
    }
}
