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

namespace MageOS\NetSuiteConnector\Core\Test\Unit\Enum\Message;

use MageOS\NetSuiteConnector\Core\Enum\Message\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The status values are persisted against every queued message and drive the cron that
 * releases stuck messages as well as the post process handlers, which compare with
 * equals(). Changing a string value strands rows that already carry the old one.
 */
class StatusTest extends TestCase
{
    /**
     * The enum exposes exactly six statuses with exactly these persisted values
     */
    public function testItExposesExactlyTheSixStatuses(): void
    {
        $this->assertSame(
            [
                'IN_QUEUE' => 'in_queue',
                'IN_PROGRESS' => 'in_progress',
                'DONE' => 'done',
                'CANCELLED' => 'cancelled',
                'ERROR' => 'error',
                'RETRY' => 'retry',
            ],
            Status::toArray()
        );
    }

    /**
     * The constant names are the keys used by the static accessors
     */
    public function testItExposesTheConstantNamesAsKeys(): void
    {
        $this->assertSame(
            ['IN_QUEUE', 'IN_PROGRESS', 'DONE', 'CANCELLED', 'ERROR', 'RETRY'],
            Status::keys()
        );
    }

    /**
     * No two statuses share a persisted value
     */
    public function testItKeepsTheStatusValuesUnique(): void
    {
        $values = Status::toArray();
        $this->assertCount(count($values), array_unique($values));
    }

    /**
     * Each status resolves to its persisted value through the value getter and the
     * string cast
     */
    #[DataProvider('statusProvider')]
    public function testItResolvesEachStatusToItsPersistedValue(string $key, string $value): void
    {
        $status = Status::from($value);

        $this->assertSame($key, $status->getKey());
        $this->assertSame($value, $status->getValue());
        $this->assertSame($value, (string)$status);
    }

    /**
     * The status keys paired with the values stored against a queued message
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function statusProvider(): array
    {
        return [
            'in queue' => ['IN_QUEUE', 'in_queue'],
            'in progress' => ['IN_PROGRESS', 'in_progress'],
            'done' => ['DONE', 'done'],
            'cancelled' => ['CANCELLED', 'cancelled'],
            'error' => ['ERROR', 'error'],
            'retry' => ['RETRY', 'retry'],
        ];
    }

    /**
     * The static accessors return the same values as the constant table
     */
    public function testItReturnsTheSameValuesThroughTheStaticAccessors(): void
    {
        $this->assertSame('in_queue', Status::IN_QUEUE()->getValue());
        $this->assertSame('in_progress', Status::IN_PROGRESS()->getValue());
        $this->assertSame('done', Status::DONE()->getValue());
        $this->assertSame('cancelled', Status::CANCELLED()->getValue());
        $this->assertSame('error', Status::ERROR()->getValue());
        $this->assertSame('retry', Status::RETRY()->getValue());
    }

    /**
     * A value that is not a status is rejected rather than accepted as a new state
     */
    public function testItRejectsAValueThatIsNotAStatus(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        new Status('processing');
    }

    /**
     * Validation of status values is case sensitive and does not accept the constant name
     */
    #[DataProvider('invalidStatusValueProvider')]
    public function testItRejectsValuesThatOnlyLookLikeStatuses(string $value): void
    {
        $this->assertFalse(Status::isValid($value));
    }

    /**
     * Near misses that must not be accepted as status values
     *
     * @return array<string, array{0: string}>
     */
    public static function invalidStatusValueProvider(): array
    {
        return [
            'constant name' => ['IN_QUEUE'],
            'uppercase value' => ['DONE'],
            'hyphenated value' => ['in-queue'],
            'spaced value' => ['in queue'],
            'plural' => ['errors'],
            'empty string' => [''],
        ];
    }

    /**
     * An accessor for a status that does not exist is rejected rather than silently
     * returning a usable object
     */
    public function testItRejectsAnUnknownStaticAccessor(): void
    {
        $this->expectException(\BadMethodCallException::class);
        Status::SKIPPED();
    }

    /**
     * Two instances of the same status compare as equal, different statuses do not,
     * which is the comparison the reservation compensation handler performs
     */
    public function testItComparesStatusesByValue(): void
    {
        $this->assertTrue(Status::DONE()->equals(Status::DONE()));
        $this->assertTrue(Status::DONE()->equals(new Status('done')));
        $this->assertFalse(Status::DONE()->equals(Status::ERROR()));
        $this->assertFalse(Status::IN_QUEUE()->equals(Status::IN_PROGRESS()));
    }

    /**
     * Comparison against a raw string or null returns false rather than raising, so a
     * caller that forgets to wrap the value gets a silent negative
     */
    public function testItDoesNotCompareEqualToRawValues(): void
    {
        $this->assertFalse(Status::DONE()->equals('done'));
        $this->assertFalse(Status::DONE()->equals(null));
    }

    /**
     * A status serialises to its persisted value
     */
    public function testItSerialisesToItsPersistedValue(): void
    {
        $this->assertSame('"done"', json_encode(Status::DONE()));
    }
}
