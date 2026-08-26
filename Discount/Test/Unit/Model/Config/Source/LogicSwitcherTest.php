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

namespace MageOS\NetSuiteConnector\Discount\Test\Unit\Model\Config\Source;

use MageOS\NetSuiteConnector\Discount\Model\Config\Source\LogicSwitcher;
use Magento\Framework\Option\ArrayInterface;
use PHPUnit\Framework\TestCase;

class LogicSwitcherTest extends TestCase
{
    /**
     * @var LogicSwitcher
     */
    private LogicSwitcher $source;

    /**
     * Build the option source under test.
     */
    protected function setUp(): void
    {
        $this->source = new LogicSwitcher();
    }

    /**
     * The source must satisfy the interface the system configuration expects.
     */
    public function testItIsAnOptionSource(): void
    {
        $this->assertInstanceOf(ArrayInterface::class, $this->source);
    }

    /**
     * The body value is read by the discount mappers, so it must keep its exact literal value.
     */
    public function testItPinsTheBodyValue(): void
    {
        $this->assertSame('body', LogicSwitcher::BODY);
    }

    /**
     * Exactly two discount strategies are offered and both keep their stored values.
     */
    public function testItOffersBothDiscountStrategies(): void
    {
        $values = array_column($this->source->toOptionArray(), 'value');

        $this->assertSame(['body', 'line'], $values);
    }

    /**
     * Each discount strategy must carry the label the admin sees.
     */
    public function testItLabelsEveryDiscountStrategy(): void
    {
        $options = array_map(
            static fn (array $option): array => [
                'value' => $option['value'],
                'label' => (string)$option['label'],
            ],
            $this->source->toOptionArray()
        );

        $this->assertSame(
            [
                ['value' => 'body', 'label' => 'Using Discount Item Field'],
                ['value' => 'line', 'label' => 'Adding Discount to Item List'],
            ],
            $options
        );
    }
}
