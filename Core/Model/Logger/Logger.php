<?php declare(strict_types=1);

namespace MageOS\NetSuiteConnector\Core\Model\Logger;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Works against Monolog 2 and Monolog 3.
 *
 * The record and level parameters stay untyped so the declaration satisfies both parent
 * signatures, which changed between the two majors. Keep them untyped.
 */
class Logger extends \Monolog\Logger
{
    private ?\Symfony\Component\Console\Output\OutputInterface $output = null;
    private ?\Symfony\Component\Console\Input\InputInterface $input = null;
    private \MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig $developerConfig;
    private ?int $loggerLevel = null;

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig $developerConfig,
        $name,
        array $handlers = [],
        array $processors = []
    ) {
        $handlers = array_values($handlers);

        parent::__construct($name, $handlers, $processors);
        $this->developerConfig = $developerConfig;
    }

    public function setCli(OutputInterface $output, InputInterface $input)
    {
        $this->input = $input;
        $this->output = $output;
    }

    /**
     * @param int|\Monolog\Level $level
     * @param \DateTimeImmutable|null $datetime
     */
    public function addRecord($level, string $message, array $context = [], $datetime = null): bool
    {
        $levelInt = is_object($level) ? (int)$level->value : (int)$level;
        if ($levelInt < $this->getDynamicLevel()) {
            return true;
        }

        if ($this->output !== null && $this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
            $context['output'] = $this->output;
        }

        return parent::addRecord($level, $message, $context, $datetime);
    }

    private function getDynamicLevel(): int
    {
        if ($this->loggerLevel !== null) {
            return $this->loggerLevel;
        }
        $level = self::ERROR;

        $dbLevel = $this->developerConfig->getLoggerLevel();
        if (is_string($dbLevel) && $dbLevel !== '') {
            $level = $this->resolveLevelName($dbLevel) ?? $level;
        }

        if ($this->output !== null) {
            $level = $this->getOutputLevel() ?? $level;
        }

        $this->loggerLevel = $level;

        return $level;
    }

    private function resolveLevelName(string $name): ?int
    {
        if (enum_exists(\Monolog\Level::class)) {
            try {
                return \Monolog\Level::fromName(ucfirst(strtolower($name)))->value;
            } catch (\ValueError $e) {
                return null;
            }
        }

        $found = array_search($name, self::$levels, true);

        return $found === false ? null : (int)$found;
    }

    private function getOutputLevel(): ?int
    {
        $level = null;
        $verboseLevel = $this->output->getVerbosity();
        switch ($verboseLevel) {
            case OutputInterface::VERBOSITY_VERBOSE:
            case OutputInterface::VERBOSITY_VERY_VERBOSE:
                $level = self::INFO;
                break;
            case OutputInterface::VERBOSITY_DEBUG:
                $level = self::DEBUG;
                break;
            case OutputInterface::VERBOSITY_QUIET:
                $level = 0;
                break;
        }

        if ($this->input !== null && $this->input->hasOption('debug') && $this->input->getOption('debug')) {
            $level = self::DEBUG;
        }

        return $level;
    }

    /**
     * @deprecated since Adobe Commerce 2.4.4. Use info() method instead
     */
    public function addInfo($message, array $context = []): void
    {
        $this->info($message, $context);
    }

    /**
     * @deprecated since Adobe Commerce 2.4.4. Use debug() method instead
     */
    public function addDebug($message, array $context = []): void
    {
        $this->debug($message, $context);
    }

    /**
     * @deprecated since Adobe Commerce 2.4.4. Use error() method instead
     */
    public function addError($message, array $context = []): void
    {
        $this->error($message, $context);
    }

    /**
     * @deprecated since Adobe Commerce 2.4.4. Use critical() method instead
     */
    public function addCritical($message, array $context = []): void
    {
        $this->critical($message, $context);
    }
}
