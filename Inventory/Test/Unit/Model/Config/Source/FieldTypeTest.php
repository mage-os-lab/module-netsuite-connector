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

use MageOS\NetSuiteConnector\Inventory\Model\Config\Source\FieldType;
use Magento\Framework\Data\OptionSourceInterface;
use PHPUnit\Framework\TestCase;

class FieldTypeTest extends TestCase
{
    /**
     * @var FieldType
     */
    private FieldType $source;

    /**
     * Build the option source under test.
     */
    protected function setUp(): void
    {
        $this->source = new FieldType();
    }

    /**
     * The source must satisfy the interface the system configuration expects.
     */
    public function testItIsAnOptionSource(): void
    {
        $this->assertInstanceOf(OptionSourceInterface::class, $this->source);
    }

    /**
     * The publicly exposed field type constants must keep their exact stored values.
     */
    public function testItPinsTheFieldTypeValues(): void
    {
        $this->assertSame('standard', FieldType::FIELD_TYPE_STANDARD);
        $this->assertSame('custom', FieldType::FIELD_TYPE_CUSTOM);
    }

    /**
     * Exactly the standard and custom field types are offered, with the labels the admin sees.
     */
    public function testItOffersTheStandardAndCustomFieldTypes(): void
    {
        $options = array_map('strval', $this->source->toArray());

        $this->assertSame(
            [
                'standard' => 'Standard',
                'custom' => 'Custom',
            ],
            $options
        );
    }

    /**
     * The option array must pair each label with the value stored in the stock configuration.
     */
    public function testItConvertsTheFieldTypesIntoLabelAndValuePairs(): void
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
                ['label' => 'Standard', 'value' => 'standard'],
                ['label' => 'Custom', 'value' => 'custom'],
            ],
            $options
        );
    }
}
