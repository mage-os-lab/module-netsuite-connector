<?php
/**
 * ArrayAdapterFactory
 *
 * @copyright Copyright © 2017 RocketWeb. All rights reserved.
 * @author    stan.smovdorenko@rocketweb.com
 */

namespace MageOS\NetSuiteConnector\Core\Model\ImportExport;

use Magento\ImportExport\Model\Import\AbstractSource;

class ArrayAdapter extends AbstractSource
{
    /** @var int */
    protected $position;
    /**
     * @var array
     */
    private $data;

    /**
     * ArrayAdapter constructor.
     * @param array $data
     * @throws \InvalidArgumentException
     */
    public function __construct(array $data)
    {
        $this->position = 0;
        $this->data = $data;

        parent::__construct(
            array_keys($this->current())
        );
    }

    public function next()
    {
        ++$this->position;
    }

    public function current()
    {
        $row = $this->data[$this->position];
        return $row;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->data[$this->position]);
    }

    public function seek($position)
    {
        $this->position = $position;

        if (!$this->valid()) {
            throw new \OutOfBoundsException("Can't seek to $position.");
        }
    }

    public function getColNames()
    {
        $colNames = [];
        foreach ($this->data as $row) {
            foreach (array_keys($row) as $key) {
                if (!is_numeric($key) && !isset($colNames[$key])) {
                    $colNames[$key] = $key;
                }
            }
        }
        return $colNames;
    }

    protected function _getNextRow()
    {
        $this->next();
        return $this->current();
    }
}
