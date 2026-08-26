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

namespace MageOS\NetSuiteConnector\CustomerImport\Test\Unit\Model\Config\Source;

use MageOS\NetSuiteConnector\CustomerImport\Model\Config\Source\AddressFields;
use Magento\Framework\Data\OptionSourceInterface;
use PHPUnit\Framework\TestCase;

class AddressFieldsTest extends TestCase
{
    /**
     * @var AddressFields
     */
    private AddressFields $source;

    /**
     * Build the option source under test.
     */
    protected function setUp(): void
    {
        $this->source = new AddressFields();
    }

    /**
     * The source must satisfy the interface the system configuration expects.
     */
    public function testItIsAnOptionSource(): void
    {
        $this->assertInstanceOf(OptionSourceInterface::class, $this->source);
    }

    /**
     * The stored values are the NetSuite address field names, so they must stay exactly as they are.
     */
    public function testItOffersTheNetSuiteAddressFieldNames(): void
    {
        $this->assertSame(
            ['phone', 'addr1', 'zip', 'city', 'state', 'country'],
            array_column($this->source->toOptionArray(), 'value')
        );
    }

    /**
     * Each address field must carry the label the admin sees.
     */
    public function testItLabelsEveryAddressField(): void
    {
        $this->assertSame(
            [
                ['value' => 'phone', 'label' => 'Telephone'],
                ['value' => 'addr1', 'label' => 'Street'],
                ['value' => 'zip', 'label' => 'Postalcode'],
                ['value' => 'city', 'label' => 'City'],
                ['value' => 'state', 'label' => 'Region/State'],
                ['value' => 'country', 'label' => 'Country'],
            ],
            $this->source->toOptionArray()
        );
    }

    /**
     * The option list is a plain list without gaps so the multiselect renders every entry.
     */
    public function testItReturnsAContiguousList(): void
    {
        $options = $this->source->toOptionArray();

        $this->assertCount(6, $options);
        $this->assertSame(range(0, 5), array_keys($options));
    }
}
