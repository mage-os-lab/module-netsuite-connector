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

namespace MageOS\NetSuiteConnector\Core\Test\Unit\Model\Config\Source;

use MageOS\NetSuiteConnector\Core\Model\Config\Source\Visibility;
use Magento\Catalog\Model\Product\Visibility as CatalogVisibility;
use Magento\Framework\Data\OptionSourceInterface;
use PHPUnit\Framework\TestCase;

class VisibilityTest extends TestCase
{
    /**
     * @var Visibility
     */
    private Visibility $source;

    /**
     * Build the option source under test.
     */
    protected function setUp(): void
    {
        $this->source = new Visibility();
    }

    /**
     * The source must satisfy the interface the system configuration expects.
     */
    public function testItIsAnOptionSource(): void
    {
        $this->assertInstanceOf(OptionSourceInterface::class, $this->source);
    }

    /**
     * The stored value of every option must be a Magento catalog visibility identifier.
     */
    public function testItOffersTheCatalogVisibilityIdentifiers(): void
    {
        $this->assertSame(
            [
                CatalogVisibility::VISIBILITY_NOT_VISIBLE,
                CatalogVisibility::VISIBILITY_IN_CATALOG,
                CatalogVisibility::VISIBILITY_IN_SEARCH,
                CatalogVisibility::VISIBILITY_BOTH,
            ],
            array_keys($this->source->toArray())
        );
    }

    /**
     * Every visibility identifier must carry the label the admin sees.
     */
    public function testItLabelsEveryVisibilityIdentifier(): void
    {
        $labels = array_map('strval', $this->source->toArray());

        $this->assertSame(
            [
                1 => 'Not Visible',
                2 => 'Catalog',
                3 => 'Search',
                4 => 'Catalog, Search',
            ],
            $labels
        );
    }

    /**
     * The option array must pair each label with its value in the order of the source array.
     */
    public function testItConvertsTheSourceArrayIntoLabelAndValuePairs(): void
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
                ['label' => 'Not Visible', 'value' => 1],
                ['label' => 'Catalog', 'value' => 2],
                ['label' => 'Search', 'value' => 3],
                ['label' => 'Catalog, Search', 'value' => 4],
            ],
            $options
        );
    }
}
