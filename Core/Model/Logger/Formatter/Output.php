<?php declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Core\Model\Logger\Formatter;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;

/**
 * Works against Monolog 2 and Monolog 3.
 *
 * The record parameter stays untyped so the declaration satisfies both parent signatures.
 * Monolog 2 passes an array, Monolog 3 passes a \Monolog\LogRecord. Keep it untyped.
 */
class Output implements \Monolog\Formatter\FormatterInterface
{
    private const LEVEL_COLOR = [
        'debug' => 'cyan',
        'info' => 'green',
        'warning' => 'yellow',
        'error' => 'magenta',
        'critical' => 'red',
        'alert' => 'red',
        'emergency' => 'red'
    ];

    /**
     * @param array|\Monolog\LogRecord $record
     */
    public function format($record)
    {
        $isArray = is_array($record);
        $context = $isArray ? $record['context'] : $record->context;
        $levelName = $isArray ? $record['level_name'] : $record->level->getName();

        $newline = isset($context['newline']) && !$context['newline'] ? '' : "\n";
        $level = strtolower((string)$levelName);
        $message = $this->cleanUp($isArray ? $record['message'] : $record->message, $level);

        return $message . $newline;
    }

    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }

        return $message;
    }

    private function cleanUp(string $message, string $level): string
    {
        foreach (array_keys(self::LEVEL_COLOR) as $tag) {
            $staringTag = sprintf('<%s>', $tag);
            $endingTag = sprintf('</%s>', $tag);
            $message = str_replace($staringTag, '', $message);
            $message = str_replace($endingTag, '', $message);
        }

        $color = self::LEVEL_COLOR[$level] ?? 'default';

        return (new OutputFormatterStyle($color))->apply(
            \Symfony\Component\Console\Formatter\OutputFormatter::escape($message)
        );
    }
}
