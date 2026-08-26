<?php

namespace MageOS\NetSuiteConnector\Core\Model\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class Exception extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/netsuite_exception.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
