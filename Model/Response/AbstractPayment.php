<?php

namespace Credorax\Credorax\Model\Response;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\AbstractResponse;
use Credorax\Credorax\Model\Config;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\State\AuthorizeCommand;
use Magento\Sales\Model\Order\Payment\State\CaptureCommand;
use Magento\Sales\Model\Order\Payment\Transaction as OrderTransaction;

/**
 * Credorax Credorax abstract payment response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
abstract class AbstractPayment extends AbstractResponse
{
    /**
     * @var Order
     */
    protected $order;

    /**
     * @var OrderPayment
     */
    protected $orderPayment;

    /**
     * @var AuthorizeCommand
     */
    protected $authorizeCommand;
    /**
     * @var CaptureCommand
     */
    protected $captureCommand;

    /**
     * @var string
     */
    protected $transactionId;

    /**
     * @var string
     */
    protected $cipher;

    /**
     * @var string|null
     */
    protected $operationCode;

    /**
     * @var string
     */
    protected $responseId;

    /**
     * @var int
     */
    protected $responseCode;

    /**
     * @var string
     */
    protected $responseDescription;

    /**
     * @method __construct
     * @param  Config           $credoraxConfig
     * @param  Curl             $curl
     * @param  OrderPayment     $orderPayment
     * @param  AuthorizeCommand $authorizeCommand
     * @param  CaptureCommand   $captureCommand
     */
    public function __construct(
        Config $credoraxConfig,
        Curl $curl,
        OrderPayment $orderPayment,
        AuthorizeCommand $authorizeCommand,
        CaptureCommand $captureCommand
    ) {
        parent::__construct(
            $credoraxConfig,
            $curl
        );

        $this->order = $orderPayment->getOrder();
        $this->orderPayment = $orderPayment;
        $this->authorizeCommand = $authorizeCommand;
        $this->captureCommand = $captureCommand;
    }

    /**
     * @return Dynamic3D
     */
    protected function processResponseData()
    {
        $body = $this->getBody();

        $this->transactionId = $body['z13'];
        $this->cipher = $body['K'];
        $this->operationCode = (int)$body['O'];
        $this->responseId = isset($body['z1']) ? (int)$body['z1'] : null;
        $this->responseCode = isset($body['z2']) ? (int)$body['z2'] : 0;
        $this->responseDescription = isset($body['z3']) ? $body['z3'] : null;

        return $this;
    }

    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        parent::process();

        $this
            ->processResponseData()
            ->updateTransaction();

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    protected function getRequestStatus()
    {
        if (parent::getRequestStatus() === false) {
            return false;
        }

        $body = $this->getBody();
        if (isset($body['z2']) && $body['z2']) {
            return false;
        }

        return true;
    }

    /**
     * @return AbstractPayment
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateTransaction()
    {
        $body = $this->getBody();
        ksort($body);

        $this->orderPayment->setTransactionAdditionalInfo(
            OrderTransaction::RAW_DETAILS,
            $body
        );

        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function getOperationCode()
    {
        return $this->operationCode;
    }

    /**
     * @return string
     */
    public function getResponseId()
    {
        return $this->responseId;
    }

    /**
     * @return int
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @return string
     */
    public function getResponseDescription()
    {
        return $this->responseDescription;
    }
}
