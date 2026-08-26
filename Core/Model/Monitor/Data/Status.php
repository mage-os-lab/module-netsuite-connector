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

/**
 * @method static string|self IN_QUEUE
 * @method static string|self IN_PROGRESS
 * @method static string|self DONE
 * @method static string|self CANCELLED
 * @method static string|self ERROR
 * @method static string|self RETRY
 */
class Status extends Enum
{
    private const IN_QUEUE = 'in_queue';
    private const RETRY = 'retry';
    private const IN_PROGRESS = 'in_progress';
    private const DONE = 'done';
    private const CANCELLED = 'cancelled';
    private const ERROR = 'error';

    private const LABELS = [
        self::IN_QUEUE => 'In Queue',
        self::RETRY => 'In Queue - Retry',
        self::IN_PROGRESS => 'In Progress',
        self::DONE => 'Done',
        self::CANCELLED => 'Cancelled',
        self::ERROR => 'Error'
    ];

    // phpcs:ignore
    public static function getLabel(Status $status): string
    {
        return self::LABELS[(string)$status];
    }

    // phpcs:ignore
    public static function getLabels(): array
    {
        return self::LABELS;
    }
}
