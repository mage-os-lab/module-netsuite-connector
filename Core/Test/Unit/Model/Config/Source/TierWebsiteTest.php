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

use MageOS\NetSuiteConnector\Core\Model\Config\Source\TierWebsite;
use MageOS\NetSuiteConnector\Core\Model\Config\Source\Visibility;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\DataObject;
use Magento\Store\Model\ResourceModel\Website\Collection;
use Magento\Store\Model\ResourceModel\Website\CollectionFactory;
use PHPUnit\Framework\TestCase;

class TierWebsiteTest extends TestCase
{
    /**
     * The source must satisfy the interface the system configuration expects.
     */
    public function testItIsAnOptionSource(): void
    {
        $source = $this->createSource([]);

        $this->assertInstanceOf(OptionSourceInterface::class, $source);
        $this->assertInstanceOf(Visibility::class, $source);
    }

    /**
     * The catch all option must be offered first and must carry the website id zero.
     */
    public function testItAlwaysOffersTheAllWebsitesOptionFirst(): void
    {
        $source = $this->createSource([]);
        $options = $source->toArray();

        $this->assertSame([0], array_keys($options));
        $this->assertSame('All websites', (string)$options[0]);
    }

    /**
     * Every website from the collection must be offered keyed by its own website id.
     */
    public function testItOffersEveryWebsiteKeyedByItsWebsiteId(): void
    {
        $source = $this->createSource([
            new DataObject(['website_id' => 1, 'name' => 'Main Website']),
            new DataObject(['website_id' => 4, 'name' => 'Wholesale Website']),
        ]);

        $options = array_map('strval', $source->toArray());

        $this->assertSame(
            [
                0 => 'All websites',
                1 => 'Main Website',
                4 => 'Wholesale Website',
            ],
            $options
        );
    }

    /**
     * The option array must pair each website label with its website id.
     */
    public function testItConvertsTheWebsitesIntoLabelAndValuePairs(): void
    {
        $source = $this->createSource([
            new DataObject(['website_id' => 1, 'name' => 'Main Website']),
        ]);

        $options = array_map(
            static fn (array $option): array => [
                'label' => (string)$option['label'],
                'value' => $option['value'],
            ],
            $source->toOptionArray()
        );

        $this->assertSame(
            [
                ['label' => 'All websites', 'value' => 0],
                ['label' => 'Main Website', 'value' => 1],
            ],
            $options
        );
    }

    /**
     * A website whose id collides with the catch all option overwrites that option.
     */
    public function testAWebsiteWithIdZeroOverwritesTheAllWebsitesOption(): void
    {
        $source = $this->createSource([
            new DataObject(['website_id' => 0, 'name' => 'Admin']),
        ]);

        $options = array_map('strval', $source->toArray());

        $this->assertSame([0 => 'Admin'], $options);
    }

    /**
     * Build the subject around a website collection holding the given items.
     *
     * @param array<int, DataObject> $websites
     * @return TierWebsite
     */
    private function createSource(array $websites): TierWebsite
    {
        $collection = $this->createStub(Collection::class);
        $collection->method('getIterator')->willReturn(new \ArrayIterator($websites));

        $collectionFactory = $this->createStub(CollectionFactory::class);
        $collectionFactory->method('create')->willReturn($collection);

        return new TierWebsite($collectionFactory);
    }
}
