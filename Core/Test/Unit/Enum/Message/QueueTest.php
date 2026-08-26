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

use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;
use MageOS\NetSuiteConnector\Core\Enum\Message\Status;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The queue values are persisted in the message table and are read back through
 * Queue\Message::getQueue(). Changing one of the string values orphans every row
 * already in the queue, so the wire values are pinned here.
 */
class QueueTest extends TestCase
{
    /**
     * The enum exposes exactly two queues with exactly these persisted values
     */
    public function testItExposesExactlyTheTwoQueueNames(): void
    {
        $this->assertSame(
            [
                'IMPORT' => 'netsuite_import',
                'EXPORT' => 'netsuite_export',
            ],
            Queue::toArray()
        );
    }

    /**
     * The constant names are the keys used by the static accessors
     */
    public function testItExposesTheConstantNamesAsKeys(): void
    {
        $this->assertSame(['IMPORT', 'EXPORT'], Queue::keys());
    }

    /**
     * No two queues share a persisted value
     */
    public function testItKeepsTheQueueValuesUnique(): void
    {
        $values = Queue::toArray();
        $this->assertCount(count($values), array_unique($values));
    }

    /**
     * Each queue resolves to its persisted value through the accessor, the value getter
     * and the string cast that the order observer relies on
     */
    #[DataProvider('queueProvider')]
    public function testItResolvesEachQueueToItsPersistedValue(string $key, string $value): void
    {
        $queue = Queue::from($value);

        $this->assertSame($key, $queue->getKey());
        $this->assertSame($value, $queue->getValue());
        $this->assertSame($value, (string)$queue);
    }

    /**
     * The queue keys paired with the values stored in the message table
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function queueProvider(): array
    {
        return [
            'import' => ['IMPORT', 'netsuite_import'],
            'export' => ['EXPORT', 'netsuite_export'],
        ];
    }

    /**
     * The static accessors return the same values as the constant table
     */
    public function testItReturnsTheSameValuesThroughTheStaticAccessors(): void
    {
        $this->assertSame('netsuite_import', Queue::IMPORT()->getValue());
        $this->assertSame('netsuite_export', Queue::EXPORT()->getValue());
    }

    /**
     * A value that is not a queue name is rejected, which is the guard that fires when
     * the message table holds a queue name this build no longer knows
     */
    public function testItRejectsAValueThatIsNotAQueueName(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        new Queue('netsuite_archive');
    }

    /**
     * Validation of queue values is case sensitive and does not accept the constant name
     */
    #[DataProvider('invalidQueueValueProvider')]
    public function testItRejectsValuesThatOnlyLookLikeQueueNames(string $value): void
    {
        $this->assertFalse(Queue::isValid($value));
    }

    /**
     * Near misses that must not be accepted as queue values
     *
     * @return array<string, array{0: string}>
     */
    public static function invalidQueueValueProvider(): array
    {
        return [
            'constant name' => ['IMPORT'],
            'lowercase constant name' => ['import'],
            'uppercase value' => ['NETSUITE_IMPORT'],
            'value with whitespace' => [' netsuite_import'],
            'empty string' => [''],
        ];
    }

    /**
     * The persisted values are accepted by the validator
     */
    public function testItAcceptsThePersistedQueueValues(): void
    {
        $this->assertTrue(Queue::isValid('netsuite_import'));
        $this->assertTrue(Queue::isValid('netsuite_export'));
    }

    /**
     * An accessor for a queue that does not exist is rejected rather than silently
     * returning a usable object
     */
    public function testItRejectsAnUnknownStaticAccessor(): void
    {
        $this->expectException(\BadMethodCallException::class);
        Queue::ARCHIVE();
    }

    /**
     * Two instances of the same queue compare as equal, different queues do not
     */
    public function testItComparesQueuesByValue(): void
    {
        $this->assertTrue(Queue::IMPORT()->equals(Queue::IMPORT()));
        $this->assertTrue(Queue::IMPORT()->equals(new Queue('netsuite_import')));
        $this->assertFalse(Queue::IMPORT()->equals(Queue::EXPORT()));
    }

    /**
     * Comparison against a raw string or null returns false rather than raising, so a
     * caller that forgets to wrap the value gets a silent negative
     */
    public function testItDoesNotCompareEqualToRawValues(): void
    {
        $this->assertFalse(Queue::IMPORT()->equals('netsuite_import'));
        $this->assertFalse(Queue::IMPORT()->equals(null));
    }

    /**
     * A queue never compares equal to a status even though both are string enums
     */
    public function testItNeverComparesEqualToAStatus(): void
    {
        $this->assertFalse(Queue::IMPORT()->equals(Status::IN_QUEUE()));
    }

    /**
     * A queue serialises to its persisted value
     */
    public function testItSerialisesToItsPersistedValue(): void
    {
        $this->assertSame('"netsuite_import"', json_encode(Queue::IMPORT()));
    }
}
