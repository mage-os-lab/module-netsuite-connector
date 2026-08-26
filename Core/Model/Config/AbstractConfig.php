<?php
/**
 * AbstractConfig
 *
 * @copyright Copyright © 2018 RocketWeb. All rights reserved.
 * @author    stan.smovdorenko@rocketweb.com
 */

namespace MageOS\NetSuiteConnector\Core\Model\Config;

abstract class AbstractConfig
{
    /** @var ConfigurationResolver */
    private $resolver;

    /**
     * @param ConfigurationResolverFactory $configFactory
     */
    public function __construct(
        ConfigurationResolverFactory $configFactory
    ) {
        $this->resolver = $configFactory->create([
            'optionsMap' => $this->getOptionsMap(),
            'cacheEnabled' => $this->isCacheable()
        ]);
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public function __call($name, $arguments)
    {
        return $this->resolver->execute($name);
    }

    public function setScopeTypeAndCode($scopeType, $code)
    {
        $this->resolver->setScopeTypeAndCode($scopeType, $code);
    }

    /**
     * @return array
     */
    abstract public function getOptionsMap(): array;

    /**
     * @return bool
     */
    protected function isCacheable(): bool
    {
        return true;
    }

    /**
     * @param bool $enabled
     */
    public function setCacheEnabled($enabled = true)
    {
        $this->resolver->setCacheEnabled($enabled);
    }
}
