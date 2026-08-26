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

namespace MageOS\NetSuiteConnector\Inventory\Test\Unit\Model\Config;

use MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode;
use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InventoryModeTest extends TestCase
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
     * The publicly exposed constants must keep their exact literal values.
     */
    public function testItPinsThePublicConstants(): void
    {
        $this->assertSame('mageos_netsuite/general/inventory_mode', InventoryMode::CONFIG_PATH);
        $this->assertSame('single', InventoryMode::MODE_SINGLE);
        $this->assertSame('multi', InventoryMode::MODE_MULTI);
    }

    /**
     * The mode must be read from the exact literal config path.
     */
    public function testItReadsTheModeFromItsExactPath(): void
    {
        $mode = $this->createMode(['mageos_netsuite/general/inventory_mode' => 'multi']);

        $this->assertSame('multi', $mode->getMode());
        $this->assertContains('mageos_netsuite/general/inventory_mode', $this->requestedPaths);
    }

    /**
     * Only the exact multi value selects multi source mode, everything else falls back to single.
     */
    #[DataProvider('modeProvider')]
    public function testItFallsBackToSingleForAnythingButTheMultiValue(mixed $stored, string $expected): void
    {
        $mode = $this->createMode(['mageos_netsuite/general/inventory_mode' => $stored]);

        $this->assertSame($expected, $mode->getMode());
    }

    /**
     * Data set of stored mode values and the mode they resolve to.
     *
     * @return array<string, array{mixed, string}>
     */
    public static function modeProvider(): array
    {
        return [
            'multi' => ['multi', 'multi'],
            'single' => ['single', 'single'],
            'missing value defaults to single' => [null, 'single'],
            'empty string defaults to single' => ['', 'single'],
            'unknown value defaults to single' => ['many', 'single'],
            'capitalised multi is not recognised' => ['Multi', 'single'],
            'padded multi is not recognised' => [' multi', 'single'],
        ];
    }

    /**
     * The multi source check follows the resolved mode.
     */
    #[DataProvider('isMultiProvider')]
    public function testItReportsWhetherTheModeIsMulti(mixed $stored, bool $expected): void
    {
        $mode = $this->createMode(['mageos_netsuite/general/inventory_mode' => $stored]);

        $this->assertSame($expected, $mode->isMulti());
    }

    /**
     * Data set of stored mode values and the multi source verdict.
     *
     * @return array<string, array{mixed, bool}>
     */
    public static function isMultiProvider(): array
    {
        return [
            'multi' => ['multi', true],
            'single' => ['single', false],
            'missing value' => [null, false],
            'unknown value' => ['many', false],
        ];
    }

    /**
     * Build the subject with a scope config stub that answers only the given paths.
     *
     * @param array<string, mixed> $values
     * @return InventoryMode
     */
    private function createMode(array $values): InventoryMode
    {
        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturnCallback(
            function ($path) use ($values) {
                $this->requestedPaths[] = (string)$path;
                return $values[$path] ?? null;
            }
        );

        return new InventoryMode($scopeConfig);
    }
}
