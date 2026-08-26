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

namespace MageOS\NetSuiteConnector\Core\Cron;

use MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig;
use Magento\MysqlMq\Model\ResourceModel\MessageStatusCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Backend\App\Area\FrontNameResolver;

/**
 * Class SendQueueWarnings sends email errors and exceptions to admins about import/export queue.
 */
class SendQueueWarnings
{
    /**
     * @var string
     */
    private const QUEUE_ID_FIELD = 'queue_id';
    private const NETSUITE_IMPORT_QUEUE = 'netsuite_import';
    private const NETSUITE_EXPORT_QUEUE = 'netsuite_export';

    /**
     * @var MessageStatusCollectionFactory
     */
    private $messageStatusCollectionFactory;
    /**
     * @var DeveloperConfig
     */
    private $developerConfig;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var TransportBuilder
     */
    private $transportBuilder;
    /**
     * @var StateInterface
     */
    private $inlineTranslation;
    /**
     * @var \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig
     */
    private $connectorConfig;

    /**
     * SendQueueWarnings constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param DeveloperConfig $developerConfig
     * @param \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig
     * @param MessageStatusCollectionFactory $messageStatusCollectionFactory
     * @param Queue $queueHelper
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \MageOS\NetSuiteConnector\Core\Model\Config\DeveloperConfig $developerConfig,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig,
        \Magento\MysqlMq\Model\ResourceModel\MessageStatusCollectionFactory $messageStatusCollectionFactory,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->developerConfig = $developerConfig;
        $this->messageStatusCollectionFactory = $messageStatusCollectionFactory;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->connectorConfig = $connectorConfig;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        if (!$this->connectorConfig->isEnabled()) {
            return;
        }

        $devMailArray = $this->getDevEmails();
        if (empty($devMailArray)) {
            return;
        }

        $this->sendExportQueueThresholdMessage();
        $this->sendImportQueueThresholdMessage();
    }

    /**
     * @return void
     */
    private function sendExportQueueThresholdMessage(): void
    {
        $exportThreshold = $this->developerConfig->getExportQueueThreshold();
        $exportCollectionSize = $this->getCollectionSize(self::NETSUITE_EXPORT_QUEUE);

        if ($exportThreshold && $exportThreshold < $exportCollectionSize) {
            $this->sendEmail(
                $this->developerConfig->getExportQueueThresholdEmailTemplate(),
                [
                    'threshold' => $exportThreshold,
                    'queueCollectionSize' => $exportCollectionSize
                ]
            );
        }
    }

    /**
     * @return void
     */
    private function sendImportQueueThresholdMessage(): void
    {
        $importThreshold = $this->developerConfig->getImportQueueThreshold();
        $importCollectionSize = $this->getCollectionSize(self::NETSUITE_IMPORT_QUEUE);

        if ($importThreshold && $importThreshold < $importCollectionSize) {
            $this->sendEmail(
                $this->developerConfig->getImportQueueThresholdEmailTemplate(),
                [
                    'threshold' => $importThreshold,
                    'queueCollectionSize' => $importCollectionSize
                ]
            );
        }
    }

    private function getCollectionSize(string $queueName): int
    {
        return $this->messageStatusCollectionFactory
            ->create()
            ->addFieldToFilter(
                self::QUEUE_ID_FIELD,
                'import'//$this->queueHelper->getQueueId($queueName)
            )
            ->getSize();
    }

    /**
     * @return array
     */
    private function getDevEmails()
    {
        $devEmails = [];
        $emails = $this->developerConfig->getEmail();
        if ($emails) {
            $devEmails = explode(',', $emails);
        }

        return $devEmails;
    }

    /**
     * Send warning email using specified template with given data
     *
     * @param string $template
     * @param array $data
     */
    private function sendEmail($template, $data)
    {
        $this->inlineTranslation->suspend();
        try {
            $this->transportBuilder
                ->setTemplateIdentifier($template)
                ->setTemplateOptions([
                    'area' => FrontNameResolver::AREA_CODE,
                    'store' => Store::DEFAULT_STORE_ID,
                ])
                ->setTemplateVars($data)
                ->setFrom($this->developerConfig->getSenderEmailIdentity());

            $devMailArray = $this->getDevEmails();
            foreach ($devMailArray as $devMail) {
                $this->transportBuilder->addTo($devMail, $devMail);
            }
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
        } finally {
            $this->inlineTranslation->resume();
        }
    }
}
