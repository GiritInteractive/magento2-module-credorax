<?php


namespace Credorax\Credorax\Model\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;

/**
 * Credorax Credorax logger handler model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class ResponseHandler extends Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;
    /**
     * File name
     * @var string
     */
    protected $fileName = '/var/log/Credorax/response.log';
}