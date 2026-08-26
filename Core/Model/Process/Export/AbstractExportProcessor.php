<?php
namespace MageOS\NetSuiteConnector\Core\Model\Process\Export;

use NetSuite\Classes\AddResponse;
use NetSuite\Classes\UpdateResponse;

abstract class AbstractExportProcessor implements \MageOS\NetSuiteConnector\Core\Model\Process\Export\ExportProcessorInterface
{

    protected $response;

    abstract public function process(\MageOS\NetSuiteConnector\Core\Api\Data\MessageInterface $message);

    /**
     * @return AddResponse|UpdateResponse
     */
    public function getResponse()
    {
        return $this->response;
    }
}
