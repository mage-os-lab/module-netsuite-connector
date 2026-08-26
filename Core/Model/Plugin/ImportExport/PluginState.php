<?php
/**
 * PluginState
 *
 * @copyright Copyright © 2017 RocketWeb. All rights reserved.
 * @author    stan.smovdorenko@rocketweb.com
 */

namespace MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport;

/**
 * Class PluginState
 *
 * This is used by ImportExport plugins to determine whether they should be enabled
 *
 * @package MageOS\NetSuiteConnector\Core\Model\Plugin\ImportExport
 */
class PluginState
{
    /**
     * @var bool
     */
    protected $importRunning;

    /**
     * PluginState constructor.
     */
    public function __construct()
    {
        $this->importRunning = false;
    }

    /**
     * @return bool
     */
    public function isRunning()
    {
        return $this->importRunning;
    }

    /**
     * @param $running
     */
    public function setRunning($running)
    {
        $this->importRunning = $running;
    }
}
