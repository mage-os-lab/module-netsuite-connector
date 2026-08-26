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

namespace MageOS\NetSuiteConnector\Core\Command\Maintenance;

use Magento\Framework\Exception\LocalizedException;
use MageOS\NetSuiteConnector\Core\Command\AbstractNSCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SendQueueWarnings - CLI command to send warings for thresholds queues
 */
class SendQueueWarnings extends AbstractNSCommand
{
    /** @var \MageOS\NetSuiteConnector\Core\Cron\SendQueueWarningsFactory */
    protected $cronFactory;

    /**
     * @param \MageOS\NetSuiteConnector\Core\Cron\SendQueueWarningsFactory $cronFactory
     */
    public function __construct(\MageOS\NetSuiteConnector\Core\Cron\SendQueueWarningsFactory $cronFactory)
    {
        $this->cronFactory = $cronFactory;
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('netsuite:maintenance:sendqueuewarning')
            ->setDescription('Manually sends warnings for over threshold queues running the same logic as crojob');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws LocalizedException
     * @SuppressWarnings("unused")
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setAdminAppArea();
        $this->cronFactory->create()->execute();
        $this->logger->addInfo('Done');

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
