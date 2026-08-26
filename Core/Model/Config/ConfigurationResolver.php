<?php
/**
 * ConfigurationResolver
 *
 * @copyright Copyright © 2018 RocketWeb. All rights reserved.
 * @author    stan.smovdorenko@rocketweb.com
 */

namespace MageOS\NetSuiteConnector\Core\Model\Config;

use Magento\Framework\Api\SimpleDataObjectConverter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\SerializerInterface;

class ConfigurationResolver
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    private $registeredOptions;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var bool
     */
    private $cacheEnabled;

    /**
     * @var array
     */
    private $cache;

    /**
     * @var string
     */
    private $scopeType = \Magento\Framework\App\Config\ScopeConfigInterface::SCOPE_TYPE_DEFAULT;

    /**
     * @var string
     */
    private $scopeCode = null;

    /**
     * ConfigurationResolver constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param SerializerInterface $serializer
     * @param array $optionsMap
     * @param bool $cacheEnabled
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        SerializerInterface $serializer,
        array $optionsMap,
        bool $cacheEnabled = true
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->cache = [];

        foreach ($optionsMap as $configKey => $type) {
            $keyParts = explode('/', $configKey);
            $keyName = array_pop($keyParts);
            $methodName = 'get' . SimpleDataObjectConverter::snakeCaseToUpperCamelCase($keyName);
            switch ($type) {
                case 'int':
                    $this->registeredOptions[$methodName] = ['resolveInt', $configKey];
                    break;
                case 'bool':
                    $this->registeredOptions[$methodName] = ['resolveBool', $configKey];
                    break;
                case 'float':
                    $this->registeredOptions[$methodName] = ['resolveFloat', $configKey];
                    break;
                case 'serialized':
                    $this->registeredOptions[$methodName] = ['resolveSerialized', $configKey];
                    break;
                case 'json':
                    $this->registeredOptions[$methodName] = ['resolveJson', $configKey];
                    break;
                case 'csv':
                    $this->registeredOptions[$methodName] = ['resolveCsv', $configKey];
                    break;
                default:
                    $this->registeredOptions[$methodName] = ['resolveString', $configKey];
                    break;
            }
        }

        $this->serializer = $serializer;
        $this->cacheEnabled = $cacheEnabled;
    }

    /**
     * @param $method
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function execute(string $method)
    {
        $resolver = $this->registeredOptions[$method] ?? null;
        if ($resolver) {
            $configKey = $resolver[1];
            if ($this->cacheEnabled) {
                if (isset($this->cache[$configKey])) {
                    return $this->cache[$configKey];
                }

                $value = \call_user_func_array([$this, $resolver[0]], [$configKey]); // phpcs:ignore
                $this->cache[$configKey] = $value;
                return $value;
            }

            $value = \call_user_func_array([$this, $resolver[0]], [$configKey]); // phpcs:ignore

            return $value;
        }

        throw new \InvalidArgumentException("Can't resolve option: " . \get_class($this) . "::$method");
    }

    public function setScopeTypeAndCode($scopeType, $code)
    {
        $this->scopeType = $scopeType;
        $this->scopeCode = $code;
    }

    /**
     * @param $configKey
     * @return string
     */
    public function resolveString($configKey): string
    {
        $configValue = $this->scopeConfig->getValue($configKey, $this->scopeType, $this->scopeCode);
        return $configValue ?? '';
    }

    /**
     * @param $configKey
     * @return int
     */
    public function resolveInt($configKey): int
    {
        return (int) $this->scopeConfig->getValue($configKey, $this->scopeType, $this->scopeCode);
    }

    /**
     * @param $configKey
     * @return bool
     */
    public function resolveBool($configKey): bool
    {
        return (bool) $this->scopeConfig->getValue($configKey, $this->scopeType, $this->scopeCode);
    }

    /**
     * @param $configKey
     * @return float
     */
    public function resolveFloat($configKey): float
    {
        return (float) $this->scopeConfig->getValue($configKey, $this->scopeType, $this->scopeCode);
    }

    /**
     * @param $configKey
     * @return mixed|null
     */
    public function resolveSerialized($configKey)
    {
        $value = $this->scopeConfig->getValue($configKey, $this->scopeType, $this->scopeCode);
        return $value ? unserialize($value) : null; // phpcs:ignore
    }

    /**
     * @param $configKey
     * @return array
     */
    public function resolveCsv($configKey): array
    {
        $value = $this->scopeConfig->getValue($configKey, $this->scopeType, $this->scopeCode);
        $value = array_map('trim', explode(',', (string)$value));
        return $value ? array_filter($value) : [];
    }

    /**
     * @param $configKey
     * @return array|bool|float|int|null|string
     * @throws \InvalidArgumentException
     */
    public function resolveJson($configKey)
    {
        $value = $this->scopeConfig->getValue($configKey, $this->scopeType, $this->scopeCode);
        try {
            return $value ? $this->serializer->unserialize($value) : null;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @param bool $enabled
     */
    public function setCacheEnabled($enabled = true)
    {
        $this->cacheEnabled = $enabled;
    }
}
