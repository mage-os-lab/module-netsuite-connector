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

namespace MageOS\NetSuiteConnector\Core\Test\Unit\Model\NetSuite;

use MageOS\NetSuiteConnector\Core\Model\NetSuite\ConvertDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The value produced here is written to netsuite_last_import_date and to the product
 * and credit memo last-modified attributes. Every incremental import compares against
 * that stored value, so a wrong conversion does not surface as an error, it silently
 * decides whether the next run skips or reprocesses the record.
 */
class ConvertDateTest extends TestCase
{
    private const SQL_DATETIME_PATTERN = '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/';

    /**
     * @var string
     */
    private string $originalTimezone;

    /**
     * Pin the timezone so the expected values do not depend on the runner configuration
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalTimezone = date_default_timezone_get();
        date_default_timezone_set('America/Los_Angeles');
    }

    /**
     * Restore the timezone the runner started with
     */
    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        parent::tearDown();
    }

    /**
     * A NetSuite timestamp converts to the matching wall-clock time in the server timezone
     */
    #[DataProvider('netSuiteTimestampProvider')]
    public function testItConvertsNetSuiteTimestampsToSqlDatetime(string $netSuiteDate, string $expected): void
    {
        $this->assertSame($expected, ConvertDate::fromNetSuiteToSql($netSuiteDate));
    }

    /**
     * Real NetSuite offset formats paired with the value expected in America/Los_Angeles
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function netSuiteTimestampProvider(): array
    {
        return [
            'pacific standard offset in winter' => [
                '2024-01-22T07:40:21.000-08:00',
                '2024-01-22 07:40:21',
            ],
            'pacific daylight offset in summer' => [
                '2024-07-15T09:15:00.000-07:00',
                '2024-07-15 09:15:00',
            ],
            'daylight offset supplied for a winter date' => [
                '2024-01-22T07:40:21.000-07:00',
                '2024-01-22 06:40:21',
            ],
            'half hour offset' => [
                '2024-07-15T23:59:59.000+05:30',
                '2024-07-15 11:29:59',
            ],
            'zulu suffix' => [
                '2024-01-22T07:40:21Z',
                '2024-01-21 23:40:21',
            ],
            'zero offset' => [
                '2024-01-22T15:40:21+00:00',
                '2024-01-22 07:40:21',
            ],
            'value already in sql shape' => [
                '2024-01-22 07:40:21',
                '2024-01-22 07:40:21',
            ],
        ];
    }

    /**
     * The return value is a string shaped like an SQL datetime
     */
    public function testItReturnsAnSqlDatetimeShapedString(): void
    {
        $converted = ConvertDate::fromNetSuiteToSql('2024-01-22T07:40:21.000-08:00');
        $this->assertIsString($converted);
        $this->assertMatchesRegularExpression(self::SQL_DATETIME_PATTERN, $converted);
    }

    /**
     * Converting an already converted value returns the same value, so a stored date
     * that is fed back through the converter does not drift
     */
    public function testItIsIdempotent(): void
    {
        $once = ConvertDate::fromNetSuiteToSql('2024-01-22T07:40:21.000-08:00');
        $twice = ConvertDate::fromNetSuiteToSql($once);
        $this->assertSame($once, $twice);
    }

    /**
     * The same instant is rendered differently depending on the server timezone,
     * which means the timezone of the server decides the value that is stored
     */
    public function testItRendersTheInstantInTheServerTimezone(): void
    {
        $netSuiteDate = '2024-01-22T07:40:21.000-08:00';

        date_default_timezone_set('UTC');
        $this->assertSame('2024-01-22 15:40:21', ConvertDate::fromNetSuiteToSql($netSuiteDate));

        date_default_timezone_set('Europe/Warsaw');
        $this->assertSame('2024-01-22 16:40:21', ConvertDate::fromNetSuiteToSql($netSuiteDate));

        date_default_timezone_set('America/Los_Angeles');
        $this->assertSame('2024-01-22 07:40:21', ConvertDate::fromNetSuiteToSql($netSuiteDate));
    }

    /**
     * The literal now is accepted and resolves to the current time, which is the
     * fallback Order/Model/Process/Import/Order.php passes when lastModifiedDate is missing
     */
    public function testItAcceptsTheNowLiteralUsedByTheOrderImporter(): void
    {
        $before = time();
        $converted = ConvertDate::fromNetSuiteToSql('now');
        $after = time();

        $this->assertMatchesRegularExpression(self::SQL_DATETIME_PATTERN, $converted);
        $this->assertGreaterThanOrEqual($before, strtotime($converted));
        $this->assertLessThanOrEqual($after, strtotime($converted));
    }

    /**
     * An unparsable timestamp raises a TypeError instead of returning false, because
     * strtotime returns false and the strict_types declaration blocks date from accepting it
     */
    #[DataProvider('unparsableTimestampProvider')]
    public function testItThrowsATypeErrorWhenTheTimestampCannotBeParsed(string $netSuiteDate): void
    {
        $this->expectException(\TypeError::class);
        ConvertDate::fromNetSuiteToSql($netSuiteDate);
    }

    /**
     * Inputs that strtotime cannot parse at all
     *
     * @return array<string, array{0: string}>
     */
    public static function unparsableTimestampProvider(): array
    {
        return [
            'empty string' => [''],
            'plain text' => ['not-a-date'],
            'single zero' => ['0'],
            'the word null' => ['null'],
            'out of range components' => ['2024-13-45T99:99:99-08:00'],
            'slash separated garbage' => ['2024/99/99'],
            'valid timestamp with trailing junk' => ['2024-01-22T07:40:21.000-08:00 extra'],
        ];
    }

    /**
     * A null timestamp raises a TypeError, so the callers that read a possibly null
     * lastModifiedDate cannot rely on the false return value the docblocks describe
     */
    public function testItThrowsATypeErrorWhenTheTimestampIsNull(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line argument.type */
        ConvertDate::fromNetSuiteToSql(null);
    }

    /**
     * A whitespace only timestamp is silently accepted and resolves to the current time
     * rather than failing, which stamps a record as freshly modified
     */
    public function testItSilentlyTreatsWhitespaceOnlyInputAsTheCurrentTime(): void
    {
        $before = time();
        $converted = ConvertDate::fromNetSuiteToSql('   ');
        $after = time();

        $this->assertGreaterThanOrEqual($before, strtotime($converted));
        $this->assertLessThanOrEqual($after, strtotime($converted));
    }

    /**
     * The MySQL zero date is silently converted to a negative year rather than rejected
     */
    public function testItSilentlyConvertsTheZeroDateToANegativeYear(): void
    {
        $this->assertSame('-0001-11-30 00:00:00', ConvertDate::fromNetSuiteToSql('0000-00-00 00:00:00'));
    }

    /**
     * A calendar date that does not exist silently rolls over into the next month
     */
    public function testItSilentlyRollsOverAnImpossibleCalendarDate(): void
    {
        $this->assertSame('2024-03-01 00:00:00', ConvertDate::fromNetSuiteToSql('2024-02-30T00:00:00-08:00'));
    }
}
