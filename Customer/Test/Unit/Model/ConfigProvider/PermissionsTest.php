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

namespace MageOS\NetSuiteConnector\Customer\Test\Unit\Model\ConfigProvider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use MageOS\NetSuiteConnector\Customer\Model\ConfigProvider\Permissions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Covers the customer export feature flag.
 */
class PermissionsTest extends TestCase
{
    private const SEND_CUSTOMERS_PATH = 'mageos_netsuite/enable_disable_features/send_customers';

    /**
     * The known feature code reports exactly what the scope configuration flag says.
     */
    #[DataProvider('flagProvider')]
    public function testItReturnsTheConfiguredFlagForTheKnownFeatureCode(bool $flag): void
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturn($flag);
        $permissions = new Permissions($scopeConfig);

        $this->assertSame($flag, $permissions->isFeatureEnabled(Permissions::SEND_CUSTOMERS));
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
            'singular near miss' => ['send_customer'],
            'full path instead of a code' => [self::SEND_CUSTOMERS_PATH],
        ];
    }

    /**
     * The flag is read from the shared feature path with the feature code appended, and nothing else is read.
     */
    public function testItQueriesTheExactConfigurationPath(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->once())
            ->method('isSetFlag')
            ->with(self::SEND_CUSTOMERS_PATH)
            ->willReturn(true);
        $permissions = new Permissions($scopeConfig);

        $this->assertTrue($permissions->isFeatureEnabled(Permissions::SEND_CUSTOMERS));
    }

    /**
     * An unknown feature code never reaches the scope configuration at all.
     */
    public function testItDoesNotTouchScopeConfigForAnUnknownFeatureCode(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->expects($this->never())->method('isSetFlag');
        $permissions = new Permissions($scopeConfig);

        $this->assertFalse($permissions->isFeatureEnabled('not_a_customer_feature'));
    }
}
