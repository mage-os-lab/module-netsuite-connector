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

namespace MageOS\NetSuiteConnector\Shipment\Test\Unit\Model\Config\Source;

use MageOS\NetSuiteConnector\Shipment\Model\Config\Source\TrackingType;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Config;
use PHPUnit\Framework\TestCase;

class TrackingTypeTest extends TestCase
{
    /**
     * The source must satisfy the interface the system configuration expects.
     */
    public function testItIsAnOptionSource(): void
    {
        $this->assertInstanceOf(OptionSourceInterface::class, $this->createSource([]));
    }

    /**
     * The custom entry must always be offered first and must keep the value the shipping config expects.
     */
    public function testItAlwaysOffersTheCustomEntryFirst(): void
    {
        $source = $this->createSource([]);
        $options = array_map('strval', $source->toArray());

        $this->assertSame(['custom' => 'Custom Value'], $options);
    }

    /**
     * Every carrier that supports tracking must be offered keyed by its carrier code.
     */
    public function testItOffersEveryTrackingCapableCarrier(): void
    {
        $source = $this->createSource([
            'ups' => $this->createCarrier(true, 'United Parcel Service'),
            'fedex' => $this->createCarrier(true, 'Federal Express'),
        ]);

        $options = array_map('strval', $source->toArray());

        $this->assertSame(
            [
                'custom' => 'Custom Value',
                'ups' => 'United Parcel Service',
                'fedex' => 'Federal Express',
            ],
            $options
        );
    }

    /**
     * A carrier that does not support tracking must be left out of the list.
     */
    public function testItSkipsCarriersWithoutTrackingSupport(): void
    {
        $source = $this->createSource([
            'flatrate' => $this->createCarrier(false, 'Flat Rate'),
            'ups' => $this->createCarrier(true, 'United Parcel Service'),
        ]);

        $options = array_map('strval', $source->toArray());

        $this->assertSame(
            [
                'custom' => 'Custom Value',
                'ups' => 'United Parcel Service',
            ],
            $options
        );
    }

    /**
     * The carrier title is read from the title field of the carrier configuration.
     */
    public function testItReadsTheCarrierLabelFromTheTitleField(): void
    {
        $carrier = $this->createMock(AbstractCarrier::class);
        $carrier->method('isTrackingAvailable')->willReturn(true);
        $carrier->expects($this->once())
            ->method('getConfigData')
            ->with('title')
            ->willReturn('United Parcel Service');

        $source = $this->createSource(['ups' => $carrier]);

        $this->assertSame('United Parcel Service', (string)$source->toArray()['ups']);
    }

    /**
     * The option array must pair each label with the carrier code stored in the configuration.
     */
    public function testItConvertsTheCarriersIntoLabelAndValuePairs(): void
    {
        $source = $this->createSource(['ups' => $this->createCarrier(true, 'United Parcel Service')]);

        $options = array_map(
            static fn (array $option): array => [
                'label' => (string)$option['label'],
                'value' => $option['value'],
            ],
            $source->toOptionArray()
        );

        $this->assertSame(
            [
                ['label' => 'Custom Value', 'value' => 'custom'],
                ['label' => 'United Parcel Service', 'value' => 'ups'],
            ],
            $options
        );
    }

    /**
     * Build a carrier double with the given tracking support and title.
     *
     * @param bool $trackingAvailable
     * @param string $title
     * @return AbstractCarrier
     */
    private function createCarrier(bool $trackingAvailable, string $title): AbstractCarrier
    {
        $carrier = $this->createStub(AbstractCarrier::class);
        $carrier->method('isTrackingAvailable')->willReturn($trackingAvailable);
        $carrier->method('getConfigData')->willReturn($title);

        return $carrier;
    }

    /**
     * Build the subject around a shipping config returning the given carriers.
     *
     * @param array<string, AbstractCarrier> $carriers
     * @return TrackingType
     */
    private function createSource(array $carriers): TrackingType
    {
        $shippingConfig = $this->createStub(Config::class);
        $shippingConfig->method('getAllCarriers')->willReturn($carriers);

        return new TrackingType($shippingConfig);
    }
}
