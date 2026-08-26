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

namespace MageOS\NetSuiteConnector\Core\Model\Monitor\Data;

use MyCLabs\Enum\Enum;
use MageOS\NetSuiteConnector\Core\Enum\Message\Queue;

/**
 * @method static string|Process IMPORT
 * @method static string|Process EXPORT
 */
class Process extends Enum
{
    private const IMPORT = 'import';
    private const EXPORT = 'export';

    private const LABELS = [
        self::IMPORT => 'Import',
        self::EXPORT => 'Export'
    ];

    // phpcs:ignore
    public static function translateFromQueue(Queue $queue): Process
    {
        return $queue->equals(Queue::IMPORT()) ? self::IMPORT() : self::EXPORT();
    }

    // phpcs:ignore
    public static function getLabel(Process $process): string
    {
        return self::LABELS[(string)$process];
    }

    // phpcs:ignore
    public static function getLabels(): array
    {
        return self::LABELS;
    }
}
