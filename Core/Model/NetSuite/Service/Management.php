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

namespace MageOS\NetSuiteConnector\Core\Model\NetSuite\Service;

use NetSuite\Classes\CashSale;
use NetSuite\Classes\CreditMemo;
use NetSuite\Classes\GetServerTimeRequest;
use NetSuite\Classes\InventoryItem;
use NetSuite\Classes\Invoice;
use NetSuite\Classes\ItemMatrixType;
use NetSuite\Classes\KitItem;
use NetSuite\Classes\Preferences;
use NetSuite\Classes\ReturnAuthorization;
use NetSuite\NetSuiteService;
use MageOS\NetSuiteConnector\Core\Exception\NetSuiteRuntimeException;
use MageOS\NetSuiteConnector\Core\Model\NetSuite\ResponseValidator;

/**
 * This is the Management class for \NetSuite\NetSuiteService
 * Because there are bunch of \NetSuite\Classes\* references, phpMd is complaining about coupling between objects.
 * Ignoring it.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Management
{
    private \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig;
    private \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry;
    private \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\ConstructorFactory $constructorFactory;
    protected array $netsuiteService = [];

    public function __construct(
        \MageOS\NetSuiteConnector\Core\Registry\ModuleRegistry $registry,
        \MageOS\NetSuiteConnector\Core\Model\Config\ConnectorConfig $connectorConfig,
        \MageOS\NetSuiteConnector\Core\Model\NetSuite\Service\ConstructorFactory $constructorFactory
    ) {
        $this->connectorConfig = $connectorConfig;
        $this->registry = $registry;
        $this->constructorFactory = $constructorFactory;
    }

    /**
     * @param NetSuiteService $connection
     * @param string $runMode
     */
    public function set($connection, ?string $runMode = null)
    {
        $runMode = isset($runMode) ? $runMode : $this->getCurrentRunMode();
        $this->netsuiteService[$runMode] = $connection;
    }

    /**
     * @param array|null $connectionData
     * @param string|null $runMode
     * @return NetSuiteService
     */
    public function get(?array $connectionData = null, ?string $runMode = null)
    {
        $runMode = isset($runMode) ? $runMode : $this->getCurrentRunMode();

        if (isset($this->netsuiteService[$runMode]) && ($this->netsuiteService[$runMode] instanceof NetSuiteService)) {
            return $this->netsuiteService[$runMode];
        }

        $config = $connectionData === null ?
            $this->getSavedConfig($runMode) : $this->getConfigFromParameter($connectionData);

        $options = $this->getNetSuiteServiceOptions($runMode);
        $this->netsuiteService[$runMode] = $this->constructorFactory->create([
            'config' => $config,
            'options' => $options
        ]); // new NetSuiteService($config, $options);
        $this->netsuiteService[$runMode]->setSearchPreferences(false, 10);
        $preferences = new Preferences();
        $preferences->ignoreReadOnlyFields = true;
        $this->netsuiteService[$runMode]->addHeader('preferences', $preferences);

        return $this->netsuiteService[$runMode];
    }

    /**
     * @param string $runMode
     * @return array
     *
     * The extra parameter is only there for future use or plugin use (for example - image import requires it)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getNetSuiteServiceOptions(string $runMode): array
    {
        $timeout = $this->connectorConfig->getSoapRequestTimeout();
        $options = [
            'connection_timeout' =>  $timeout < 60 ? 60 : $timeout,
            'cache_wsdl' => WSDL_CACHE_DISK
        ];

        return $options;
    }

    /**
     * @return string
     * @throws NetSuiteRuntimeException
     */
    public function getServerTime(): string
    {
        $getServerTimeRequest = new GetServerTimeRequest();
        $connectionData = $this->registry->registry('active_connection_data') ?: null;

        $getServerTimeResult = $this->retryNetSuiteQuery(function () use ($getServerTimeRequest, $connectionData) {
            return $this->get($connectionData)->getServerTime($getServerTimeRequest);
        });

        return $getServerTimeResult->getServerTimeResult->serverTime;
    }

    protected function getCurrentRunMode()
    {
        $currentRunMode = $this->registry->registry('current_run_mode');
        return $currentRunMode ? $currentRunMode : 'default';
    }

    /**
     * This method can be used to trigger retry of NetSuite Query by using a callable Parameter.
     *
     * @param callable $query
     * @param int $retry
     * @return mixed
     * @throws NetSuiteRuntimeException
     * @throws \MageOS\NetSuiteConnector\Core\Exception\DataIntegrityException
     */
    public function retryNetSuiteQuery(callable $query, int $retry = 5)
    {
        $errors = [];
        for ($i = 1; $i < $retry; $i++) {
            try {
                $response = $query();
                ResponseValidator::validate($response);
                return $response;
            } catch (NetSuiteRuntimeException $e) {
                $errors[] = $e->getMessage();
                sleep($i);// phpcs:ignore
                continue;
            }
        }

        throw new NetSuiteRuntimeException('Failed retrying: ' . implode('|', $errors));
    }

    /**
     * @param string $runMode
     * @return array
     */
    private function getSavedConfig(string $runMode): array
    {
        return [
            "endpoint" => $this->connectorConfig->getEndpoint(),
            "host" => $this->connectorConfig->getHost(),
            "account" => $this->connectorConfig->getAccountId(),
            "consumerKey" => $this->connectorConfig->getConsumerKey($runMode),
            "consumerSecret" => $this->connectorConfig->getConsumerSecret($runMode),
            "token" => $this->connectorConfig->getTokenId($runMode),
            "tokenSecret" => $this->connectorConfig->getTokenSecret($runMode),
            "logging" => false
        ];
    }

    /**
     * @param $connectionData
     * @return array
     */
    private function getConfigFromParameter($connectionData): array
    {
        return [
            "endpoint" => $this->connectorConfig->getEndpoint(),
            "host" => $connectionData['host'],
            "account" => $connectionData['account_id'],
            "consumerKey" => $connectionData['consumer_key'],
            "consumerSecret" => $connectionData['consumer_secret'],
            "token" => $connectionData['token_id'],
            "tokenSecret" => $connectionData['token_secret']
        ];
    }
}
