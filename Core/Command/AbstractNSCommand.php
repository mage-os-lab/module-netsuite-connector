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

namespace MageOS\NetSuiteConnector\Core\Command;

use Magento\Framework\App\ObjectManager;
use MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AbstractNSCommand - class to be inherited of all other NSC CLI
 */
class AbstractNSCommand extends Command
{
    protected \Magento\Framework\App\State $appState;
    protected \MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger;

    /**
     * AbstractNSCommand constructor.
     * @deprecated $context
     * @deprecated $logger
     * @param \Magento\Framework\Model\Context|null $context
     * @param \MageOS\NetSuiteConnector\Core\Model\Logger\Logger|null $logger
     */
    public function __construct(
        ?\Magento\Framework\Model\Context $context = null,
        ?\MageOS\NetSuiteConnector\Core\Model\Logger\Logger $logger = null
    ) {
        $this->logger = $logger ?: ObjectManager::getInstance()
            ->get(\MageOS\NetSuiteConnector\Core\Model\Logger\Logger::class);
        $this->appState = $context ? $context->getAppState() : ObjectManager::getInstance()
            ->get(\Magento\Framework\App\State::class);

        parent::__construct();
    }

    public function setAdminAppArea(): void
    {
        /**
         * We set the area. If its already set, it throws an exception.
         */
        try {
            $this->appState->setAreaCode(\Magento\Backend\App\Area\FrontNameResolver::AREA_CODE);
        } catch (\Exception $ex) { // phpcs:ignore
            //
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->logger->setCli($output, $input);

        parent::initialize($input, $output);
    }

    /**
     * @param ConnectorConfig $connectorConfig
     * @param OutputInterface $output
     *
     * @return bool
     */
    public function isModuleEnabled(ConnectorConfig $connectorConfig, OutputInterface $output)
    {
        if ($connectorConfig->isEnabled()) {
            return true;
        }

        $this->logger->addError('Module is disabled');
        $this->logger->addInfo(
            'For enabling module go to Store -> Configuration -> ROCKET WEB -> NetSuite -> General settings'
        );
        return false;
    }
}
