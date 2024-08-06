<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Response;

use Shift4\Shift4\Lib\Http\Client\Curl;
use Shift4\Shift4\Model\AbstractResponse;
use Shift4\Shift4\Model\Config;
use Shift4\Shift4\Model\Shift4Method;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Transaction as OrderTransaction;

/**
 * Shift4 Shift4 abstract gateway response model.
 */
abstract class AbstractGateway extends AbstractResponse
{
    /**
     * Response handlers.
     */
    const GATEWAY_CAPTURE_HANDLER = 'gateway_capture';
    const GATEWAY_VOID_HANDLER = 'gateway_void';
    const GATEWAY_REFUND_HANDLER = 'gateway_refund';

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var OrderPayment
     */
    protected $_orderPayment;

    /**
     * @var string
     */
    protected $_transactionId;

    /**
     * @var string
     */
    protected $_cipher;

    /**
     * @var string|null
     */
    protected $_operationCode;

    /**
     * @var string
     */
    protected $_responseId;

    /**
     * @var string|int
     */
    protected $_responseCode;

    /**
     * @var string
     */
    protected $_responseDescription;

    /**
     * @var string
     */
    protected $_authCode;

    /**
     * @method __construct
     * @param  Config           $shift4Config
     * @param  Curl             $curl
     * @param  OrderPayment     $orderPayment
     */
    public function __construct(
        Config $shift4Config,
        Curl $curl,
        OrderPayment $orderPayment
    ) {
        parent::__construct(
            $shift4Config,
            $curl
        );

        $this->_order = $orderPayment->getOrder();
        $this->_orderPayment = $orderPayment;
    }

    /**
     * @return string
     */
    public function getResponseType()
    {
        return AbstractResponse::RESPONSE_TYPE_GATEWAY;
    }

    /**
     * @return Dynamic3D
     */
    protected function processResponseData()
    {
        $body = $this->getBody();

        $this->_transactionId = $body['z13'];
        $this->_cipher = $body['K'];
        $this->_operationCode = (int)$body['O'];
        $this->_responseId = isset($body['z1']) ? $body['z1'] : null;
        $this->_responseCode = isset($body['z2']) ? $body['z2'] : 0;
        $this->_responseDescription = isset($body['z3']) ? $body['z3'] : null;
        $this->_authCode = isset($body['z4']) ? $body['z4'] : null;

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
        if (isset($body['z2']) && (int)$body['z2']) {
            return false;
        }

        return true;
    }

    /**
     * @return AbstractGateway
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateTransaction()
    {
        $body = $this->getBody();
        ksort($body);

        $this->_orderPayment->setTransactionAdditionalInfo(
            OrderTransaction::RAW_DETAILS,
            $body
        );

        $this->_orderPayment->setAdditionalInformation(
            Shift4Method::KEY_SHIFT4_LAST_OPERATION_CODE,
            $this->getOperationCode()
        );

        if (($transactionId = $this->getTransactionId())) {
            $this->_orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_TRANSACTION_ID,
                $transactionId
            );
        }

        if (!$this->_orderPayment->getAdditionalInformation(Shift4Method::KEY_SHIFT4_RESPONSE_ID) && ($responseId = $this->getResponseId())) {
            $this->_orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_RESPONSE_ID,
                $responseId
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->_transactionId;
    }

    /**
     * @return string
     */
    public function getOperationCode()
    {
        return $this->_operationCode;
    }

    /**
     * @return string
     */
    public function getResponseId()
    {
        return $this->_responseId;
    }

    /**
     * @return string|int
     */
    public function getResponseCode()
    {
        return $this->_responseCode;
    }

    /**
     * @return string
     */
    public function getResponseDescription()
    {
        return $this->_responseDescription;
    }

    /**
     * @return string
     */
    public function getAuthCode()
    {
        return $this->_authCode;
    }
}
