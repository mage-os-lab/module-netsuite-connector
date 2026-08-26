<?php
/**
 * ConnectorConfig
 *
 * @copyright Copyright © 2017 RocketWeb. All rights reserved.
 * @author    stan.smovdorenko@rocketweb.com
 */

namespace MageOS\NetSuiteConnector\Core\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Registry;

/**
 * Provides config options related to NetSuite connection
 *
 * @package MageOS\NetSuiteConnector\Core\Model\Config\Source
 */
class ConnectorConfig
{
    private const PATH_HOST = 'mageos_netsuite/general/host';
    public const PATH_ENDPOINT = 'mageos_netsuite/general/nsendpoint';
    private const PATH_ACCOUNT_ID = 'mageos_netsuite/general/account_id';
    private const PATH_SAME = 'mageos_netsuite/%s/same';
    public const PATH_ENABLED = 'mageos_netsuite/general/enabled';
    private const PATH_CONSUMER_KEY = 'mageos_netsuite/%s/consumer_key';
    private const PATH_CONSUMER_SECRET = 'mageos_netsuite/%s/consumer_secret';
    private const PATH_TOKEN_ID = 'mageos_netsuite/%s/token_id';
    private const PATH_TOKEN_SECRET = 'mageos_netsuite/%s/token_secret';

    private const SOAP_REQUEST_TIMEOUT = 'mageos_netsuite/general/soap_request_timeout';
    private const NETSUITE_BASE_URL = 'mageos_netsuite/general/netsuite_base_url';
    private const ERROR_RETRIES = 3;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Config cache
     * @var array
     */
    private $cache;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Registry $registry
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cache = [];
    }

    /**
     * @param string $path
     * @return string
     */
    private function getCached(string $path)
    {
        $value = $this->cache[$path] ?? null;
        if ($value === null) {
            $value = $this->scopeConfig->getValue(
                $path
            );
            $this->cache[$path] = $value;
        }

        return $value;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::PATH_ENABLED);
    }

    /**
     * @return string
     */
    public function getHost(): string
    {
        return rtrim((string)$this->getCached(self::PATH_HOST), '/');
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return (string)$this->getCached(self::PATH_ENDPOINT);
    }

    /**
     * @return string
     */
    public function getAccountId(): string
    {
        return (string)$this->getCached(self::PATH_ACCOUNT_ID);
    }

    /**
     * How many times to retry when we get error from NetSuite
     * @return int
     */
    public function getRetriesCount(): int
    {
        return self::ERROR_RETRIES;
    }

    /**
     * @return int
     */
    public function getSoapRequestTimeout(): int
    {
        return (int) $this->getCached(self::SOAP_REQUEST_TIMEOUT);
    }

    /**
     * @return string
     */
    public function getNetsuiteBaseUrl(): string
    {
        $url = $this->getCached(self::NETSUITE_BASE_URL);
        return $url ? rtrim($url, '/',) . '/' : '';
    }

    /**
     * @param string $runMode
     *
     * @return string
     */
    public function getConsumerKey(string $runMode)
    {
        return $this->getCached(
            sprintf(self::PATH_CONSUMER_KEY, $this->getSystemConfigPathForRunMode($runMode))
        );
    }

    /**
     * @param string $runMode
     *
     * @return string
     */
    public function getConsumerSecret(string $runMode)
    {
        return $this->getCached(
            sprintf(self::PATH_CONSUMER_SECRET, $this->getSystemConfigPathForRunMode($runMode))
        );
    }

    /**
     * @param string $runMode
     *
     * @return string
     */
    public function getTokenId(string $runMode)
    {
        return $this->getCached(
            sprintf(self::PATH_TOKEN_ID, $this->getSystemConfigPathForRunMode($runMode))
        );
    }

    /**
     * @param string $runMode
     *
     * @return string
     */
    public function getTokenSecret(string $runMode)
    {
        return $this->getCached(
            sprintf(self::PATH_TOKEN_SECRET, $this->getSystemConfigPathForRunMode($runMode))
        );
    }

    /**
     * @param $runMode
     * @return string
     */
    public function getSystemConfigPathForRunMode($runMode): string
    {
        switch ($runMode) {
            case 'import':
                $configPath = 'connection_import';
                break;
            case 'export':
                $configPath = 'connection_export';
                break;
            default:
                $configPath = 'general';
        }

        //if a separate connection is not defined, use the general one
        $configKey = sprintf(self::PATH_SAME, $configPath);
        if ($this->scopeConfig->getValue($configKey) === '0') {
            return $configPath;
        }

        return 'general';
    }
}
