<?php

namespace MageOS\NetSuiteConnector\Core\Model;

/**
 * Class FlatIndexState
 *
 * Controls flat indexer state.
 * We need it to disable flat indexer usage in some parts of import/export
 *
 * @package MageOS\NetSuiteConnector\Core\Model
 */
class FlatIndexState
{
    /** @var bool */
    private $disableFlatIndexer;

    /**
     * Enable indexer
     */
    public function enable()
    {
        $this->disableFlatIndexer = false;
    }

    /**
     * Disable indexer
     */
    public function disable()
    {
        $this->disableFlatIndexer = true;
    }

    /**
     * @return bool
     */
    public function isDisabled()
    {
        return $this->disableFlatIndexer ? true : false;
    }
}
