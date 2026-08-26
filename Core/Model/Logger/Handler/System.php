<?php declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Core\Model\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use MageOS\NetSuiteConnector\Core\Exception\MessageProcessor;

/**
 * Works against Monolog 2 and Monolog 3.
 *
 * The record parameter stays untyped so the declaration satisfies both parent signatures.
 * Monolog 2 passes a mutable array, Monolog 3 passes an immutable \Monolog\LogRecord. Keep it untyped.
 */
class System extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/netsuite_system.log';
    protected Exception $exceptionHandler;
    private Cli $cliHandler;

    public function __construct(
        \Magento\Framework\Filesystem\DriverInterface $filesystem,
        \MageOS\NetSuiteConnector\Core\Model\Logger\Handler\Exception $exceptionHandler,
        \MageOS\NetSuiteConnector\Core\Model\Logger\Handler\Cli $cliHandler,
        $filePath = null
    ) {
        $this->exceptionHandler = $exceptionHandler;
        $this->cliHandler = $cliHandler;
        parent::__construct($filesystem, $filePath);
    }

    /**
     * @param array|\Monolog\LogRecord $record
     */
    public function write($record): void
    {
        $context = $this->getContext($record);
        if (isset($context['exception'])) {
            $this->exceptionHandler->handle($record);

            $exception = $context['exception'];
            $message = sprintf(
                '%s: %s (for full trace see netsuite_exception.log file)',
                get_class($exception),
                MessageProcessor::getMessagesAsString($exception)
            );
            unset($context['exception']);

            if (is_array($record)) {
                $record['context'] = $context;
                $record['message'] = (string)$message;
            } else {
                $record = $record->with(message: (string)$message, context: $context);
            }
        }

        if (isset($context['output'])) {
            $this->cliHandler->handle($record);
            return;
        }

        $formatted = $this->getFormatter()->format($record);
        if (is_array($record)) {
            $record['formatted'] = $formatted;
        } else {
            $record = $record->with(formatted: $formatted);
        }

        parent::write($record);
    }

    /**
     * @param array|\Monolog\LogRecord $record
     */
    private function getContext($record): array
    {
        return is_array($record) ? $record['context'] : $record->context;
    }
}
