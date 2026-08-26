<?php
/**
 * CacheProvider
 *
 * @copyright Copyright © 2017 RocketWeb. All rights reserved.
 * @author    stan.smovdorenko@rocketweb.com
 */

namespace MageOS\NetSuiteConnector\Core\Model;

use Magento\Framework\App\Cache\Type\Config;
use Magento\Framework\App\CacheInterface;
use MageOS\NetSuiteConnector\Core\Model\Config\CacheConfig;

class CacheProvider
{
    /**
     * @var CacheInterface
     */
    private $cacheInterface;
    /**
     * @var CacheConfig
     */
    private $cacheConfig;

    /**
     * CacheProvider constructor.
     * @param CacheInterface $cacheInterface
     * @param CacheConfig $cacheConfig
     */
    public function __construct(
        CacheInterface $cacheInterface,
        CacheConfig $cacheConfig
    ) {
        $this->cacheInterface = $cacheInterface;
        $this->cacheConfig = $cacheConfig;
    }

    /**
     * @param $data
     * @param string $key
     */
    public function saveInCache($data, string $key): void
    {
        $data = json_encode($data);
        $this->cacheInterface->save($data, $key, [Config::CACHE_TAG], $this->getCacheLifetime());
    }

    /**
     * @param string $id
     * @return bool|mixed
     */
    public function loadFromCache(string $id)
    {
        $ret = $this->cacheInterface->load($id);
        if ($ret) {
            return json_decode($ret);
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function getCacheLifetime()
    {
        return $this->cacheConfig->getCacheSecondsForListsAndCustomRecords();
    }
}
