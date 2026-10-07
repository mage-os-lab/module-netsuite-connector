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
     * @var array
     */
    private $cache;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        private readonly \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cache = [];
    }

    /**
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

    public function isEnabled(): bool
    {
        return (bool) $this->scopeConfig->getValue(self::PATH_ENABLED);
    }

    /**
     * Connection values are trimmed when read as well as when saved (Backend\TrimmedValue,
     * Backend\TrimmedEncrypted), so values saved before the backend models existed work too
     */
    public function getHost(): string
    {
        return rtrim(trim((string)$this->getCached(self::PATH_HOST)), '/');
    }

    public function getEndpoint(): string
    {
        return (string)$this->getCached(self::PATH_ENDPOINT);
    }

    public function getAccountId(): string
    {
        return trim((string)$this->getCached(self::PATH_ACCOUNT_ID));
    }

    public function getRetriesCount(): int
    {
        return self::ERROR_RETRIES;
    }

    public function getSoapRequestTimeout(): int
    {
        return (int) $this->getCached(self::SOAP_REQUEST_TIMEOUT);
    }

    public function getNetsuiteBaseUrl(): string
    {
        $url = $this->getCached(self::NETSUITE_BASE_URL);
        return $url ? rtrim($url, '/',) . '/' : '';
    }

    /**
     * @return string
     */
    public function getConsumerKey(string $runMode)
    {
        return trim((string)$this->getCached(
            sprintf(self::PATH_CONSUMER_KEY, $this->getSystemConfigPathForRunMode($runMode))
        ));
    }

    /**
     * @return string
     */
    public function getConsumerSecret(string $runMode)
    {
        return trim($this->encryptor->decrypt(
            (string)$this->getCached(
                sprintf(self::PATH_CONSUMER_SECRET, $this->getSystemConfigPathForRunMode($runMode))
            )
        ));
    }

    /**
     * @return string
     */
    public function getTokenId(string $runMode)
    {
        return trim((string)$this->getCached(
            sprintf(self::PATH_TOKEN_ID, $this->getSystemConfigPathForRunMode($runMode))
        ));
    }

    /**
     * @return string
     */
    public function getTokenSecret(string $runMode)
    {
        return trim($this->encryptor->decrypt(
            (string)$this->getCached(
                sprintf(self::PATH_TOKEN_SECRET, $this->getSystemConfigPathForRunMode($runMode))
            )
        ));
    }

    public function getSystemConfigPathForRunMode($runMode): string
    {
        switch ($runMode) {
            case 'import':
                $configPath = 'connection_import';
                break;
            case 'export':
                $configPath = 'connection_export';
                break;
            case 'stock':
                $configPath = \MageOS\NetSuiteConnector\Inventory\Model\Config\StockConfig::CONNECTION_SUBPATH;
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
