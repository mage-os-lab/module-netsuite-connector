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
 *
 */

namespace MageOS\NetSuiteConnector\Core\Exception;

class MessageProcessor
{
    // phpcs:ignore
    public static function getMessagesAsString(\Throwable $ex): string
    {
        return implode("\n", self::getMessages($ex));
    }

    // phpcs:ignore
    public static function getMessages(\Throwable $ex): array
    {
        $message = [];
        $message[] = $ex->getMessage();
        $message[] = $ex->getFile() . ':' . $ex->getLine();
        $message[] = self::processPreviousException($ex);

        if ($ex instanceof \Magento\Framework\Exception\AggregateExceptionInterface) {
            foreach ($ex->getErrors() as $localizedException) {
                $message[] = $localizedException->getMessage();
                $message[] = $localizedException->getFile() . ':' . $localizedException->getLine();
                $message[] = self::processPreviousException($localizedException);
            }
        }

        return array_filter($message);
    }

    // phpcs:ignore
    private static function processPreviousException(\Throwable $ex): string
    {
        $previous = $ex->getPrevious();
        if ($previous === null) {
            return '';
        }
        return '(Cause: ' . $ex->getPrevious()->getMessage() . ') ['
            . $previous->getFile() . ':' . $previous->getLine() . ']';
    }
}
