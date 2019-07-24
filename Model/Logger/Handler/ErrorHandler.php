<?php


namespace Credorax\Credorax\Model\Logger\Handler;

use Monolog\Logger;
use Magento\Framework\Logger\Handler\Base;

class ErrorHandler extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::ERROR;
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/Credorax/error.log';
}