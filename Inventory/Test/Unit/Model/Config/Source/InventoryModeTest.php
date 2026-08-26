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

namespace MageOS\NetSuiteConnector\Inventory\Test\Unit\Model\Config\Source;

use MageOS\NetSuiteConnector\Inventory\Model\Config\InventoryMode as ModeConfig;
use MageOS\NetSuiteConnector\Inventory\Model\Config\Source\InventoryMode;
use Magento\Framework\Data\OptionSourceInterface;
use PHPUnit\Framework\TestCase;

class InventoryModeTest extends TestCase
{
    /**
     * @var InventoryMode
     */
    private InventoryMode $source;

    /**
     * Build the option source under test.
     */
    protected function setUp(): void
    {
        $this->source = new InventoryMode();
    }

    /**
     * The source must satisfy the interface the system configuration expects.
     */
    public function testItIsAnOptionSource(): void
    {
        $this->assertInstanceOf(OptionSourceInterface::class, $this->source);
    }

    /**
     * The offered values must be exactly the values the inventory mode reader recognises.
     */
    public function testItOffersTheValuesTheReaderRecognises(): void
    {
        $values = array_column($this->source->toOptionArray(), 'value');

        $this->assertSame([ModeConfig::MODE_SINGLE, ModeConfig::MODE_MULTI], $values);
        $this->assertSame(['single', 'multi'], $values);
    }

    /**
     * Each mode must carry the label the admin sees.
     */
    public function testItLabelsEveryMode(): void
    {
        $options = array_map(
            static fn (array $option): array => [
                'label' => (string)$option['label'],
                'value' => $option['value'],
            ],
            $this->source->toOptionArray()
        );

        $this->assertSame(
            [
                ['label' => 'Single location', 'value' => 'single'],
                ['label' => 'Multiple locations', 'value' => 'multi'],
            ],
            $options
        );
    }
}
