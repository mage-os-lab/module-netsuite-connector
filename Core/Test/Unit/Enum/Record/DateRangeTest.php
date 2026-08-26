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

namespace MageOS\NetSuiteConnector\Core\Test\Unit\Enum\Record;

use MageOS\NetSuiteConnector\Core\Enum\Record\DateRange;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * These values are the long option names of netsuite:utils:importdaterange and are also
 * the array keys the command hands to the date range request builder and import
 * processor. A changed value breaks the documented CLI call and produces an undefined
 * array key on the receiving side.
 */
class DateRangeTest extends TestCase
{
    /**
     * The enum exposes exactly the four command line option names, in this order
     */
    public function testItExposesExactlyTheFourOptionNames(): void
    {
        $this->assertSame(
            ['type', 'from', 'to', 'batch'],
            array_map(static fn (DateRange $case): string => $case->value, DateRange::cases())
        );
    }

    /**
     * The case names are stable because the command references them directly
     */
    public function testItExposesTheExpectedCaseNames(): void
    {
        $this->assertSame(
            ['TYPE', 'FROM_DATE', 'TO_DATE', 'BATCH_SIZE'],
            array_map(static fn (DateRange $case): string => $case->name, DateRange::cases())
        );
    }

    /**
     * No two cases share an option name, otherwise one argument would overwrite another
     * in the array the command builds
     */
    public function testItKeepsTheOptionNamesUnique(): void
    {
        $values = array_map(static fn (DateRange $case): string => $case->value, DateRange::cases());
        $this->assertCount(count($values), array_unique($values));
    }

    /**
     * Each case carries the option name the command line exposes
     */
    #[DataProvider('dateRangeProvider')]
    public function testItMapsEachCaseToItsOptionName(DateRange $case, string $expected): void
    {
        $this->assertSame($expected, $case->value);
    }

    /**
     * Each case paired with the option name it must produce
     *
     * @return array<string, array{0: DateRange, 1: string}>
     */
    public static function dateRangeProvider(): array
    {
        return [
            'record type' => [DateRange::TYPE, 'type'],
            'start of the range' => [DateRange::FROM_DATE, 'from'],
            'end of the range' => [DateRange::TO_DATE, 'to'],
            'batch size' => [DateRange::BATCH_SIZE, 'batch'],
        ];
    }

    /**
     * An option name resolves back to its case
     */
    #[DataProvider('dateRangeProvider')]
    public function testItResolvesACaseFromItsOptionName(DateRange $case, string $value): void
    {
        $this->assertSame($case, DateRange::from($value));
        $this->assertSame($case, DateRange::tryFrom($value));
    }

    /**
     * An option name the enum does not know resolves to null rather than to a case
     */
    #[DataProvider('unknownOptionNameProvider')]
    public function testItReturnsNullForAnUnknownOptionName(string $value): void
    {
        $this->assertNull(DateRange::tryFrom($value));
    }

    /**
     * Plausible option names that are not the ones this enum defines
     *
     * @return array<string, array{0: string}>
     */
    public static function unknownOptionNameProvider(): array
    {
        return [
            'start reads naturally but is not the value' => ['start'],
            'end reads naturally but is not the value' => ['end'],
            'from date spelled out' => ['from_date'],
            'batch size spelled out' => ['batch_size'],
            'case name rather than value' => ['TYPE'],
            'uppercase value' => ['FROM'],
            'empty string' => [''],
        ];
    }

    /**
     * Resolving an unknown option name with from raises a value error
     */
    public function testItThrowsForAnUnknownOptionName(): void
    {
        $this->expectException(\ValueError::class);
        DateRange::from('start');
    }

    /**
     * The enum is backed by strings, which is what lets the command use the values
     * directly as option names and array keys
     */
    public function testItIsAStringBackedEnum(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, DateRange::TYPE);
        $this->assertIsString(DateRange::TYPE->value);
    }
}
