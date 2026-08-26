<?php declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Core\Model\Logger\Handler;

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use MageOS\NetSuiteConnector\Core\Model\Logger\Formatter\Output;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Works against Monolog 2 and Monolog 3.
 *
 * The record parameter stays untyped so the declaration satisfies both parent signatures.
 * Monolog 2 passes an array, Monolog 3 passes a \Monolog\LogRecord. Keep it untyped.
 */
class Cli extends AbstractProcessingHandler
{
    /**
     * @param array|\Monolog\LogRecord $record
     */
    protected function write($record): void
    {
        $isArray = is_array($record);
        $context = $isArray ? $record['context'] : $record->context;
        if (!isset($context['output'])) {
            return;
        }

        /** @var OutputInterface $output */
        $output = $context['output'];

        $output->writeln($isArray ? $record['formatted'] : $record->formatted);
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new Output();
    }
}
