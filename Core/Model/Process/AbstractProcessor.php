<?php
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

declare(strict_types=1);
namespace MageOS\NetSuiteConnector\Core\Model\Process;

use MageOS\NetSuiteConnector\Core\Model\Process\Export\AbstractExportProcessor;
use MageOS\NetSuiteConnector\Core\Model\Process\Import\AbstractImportProcessor;

/**
 * Class ImportProcessor
 *
 * @package MageOS\NetSuiteConnector\Core\Model\Process
 */
abstract class AbstractProcessor
{
    /**
     * @var AbstractImportProcessor[]
     */
    protected $processors;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Logger\Logger
     */
    protected $logger;

    /**
     * ImportProcessor constructor.
     * @param \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger
     * @param AbstractImportProcessor[]|AbstractExportProcessor[]|null $processors
     */
    public function __construct(
        \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger,
        ?array $processors = null
    ) {
        $this->processors = $processors ?: [];
        $this->logger = $logger;
    }

    abstract public function getEntityProcessor(string $entityCode);

    /**
     * @param string $entityCode
     * @param string $instanceName
     * @return bool
     */
    protected function validate(string $entityCode, string $instanceName): bool
    {
        if (!key_exists($entityCode, $this->processors)) {
            $this->logger->addInfo("Model class not found for {$entityCode}");
            return false;
        }

        if (!$this->processors[$entityCode] instanceof $instanceName) {
            $this->logger->addError("{$entityCode} is not an instance of " . $instanceName);
            return false;
        }

        return true;
    }

    /**
     * @param string $entityCode
     * @return string
     */
    public function formatEntityCode(string $entityCode): string
    {
        return strtolower($entityCode);
    }
}
