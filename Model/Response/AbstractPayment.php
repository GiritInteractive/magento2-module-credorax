<?php

namespace Credorax\Credorax\Model\Response;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\AbstractResponse;
use Credorax\Credorax\Model\Config;
use Credorax\Credorax\Model\CredoraxMethod;
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
     * Response handlers.
     */
    const PAYMENT_SALE_HANDLER = 'payment_sale';
    const PAYMENT_AUTH_HANDLER = 'payment_auth';

    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var OrderPayment
     */
    protected $_orderPayment;

    /**
     * @var AuthorizeCommand
     */
    protected $_authorizeCommand;
    /**
     * @var CaptureCommand
     */
    protected $_captureCommand;

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
     * @var int
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
     * @var int
     */
    protected $_ccNumber;

    /**
     * @var string
     */
    protected $_ccExpMonth;

    /**
     * @var string
     */
    protected $_ccExpYear;

    /**
     * @var string
     */
    protected $_ccOwner;

    /**
     * @var string
     */
    protected $_3dsAcsurl;

    /**
     * @var string
     */
    protected $_3dsCavv;

    /**
     * @var string
     */
    protected $_3dsEci;

    /**
     * @var string
     */
    protected $_3dsStatus;

    /**
     * @var string
     */
    protected $_3dsTrxid;

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

        $this->_order = $orderPayment->getOrder();
        $this->_orderPayment = $orderPayment;
        $this->_authorizeCommand = $authorizeCommand;
        $this->_captureCommand = $captureCommand;
    }

    /**
     * @return Dynamic3D
     */
    protected function processResponseData()
    {
        $body = $this->getBody();

        $this->_transactionId = ($this->_credoraxConfig->is3dSecureEnabled() && isset($body['3ds_status'])) ? null : $body['z13'];
        $this->_cipher = $body['K'];
        $this->_operationCode = (int)$body['O'];
        $this->_responseId = isset($body['z1']) ? $body['z1'] : null;
        $this->_responseCode = isset($body['z2']) ? (int)$body['z2'] : 0;
        $this->_responseDescription = isset($body['z3']) ? $body['z3'] : null;
        $this->_authCode = isset($body['z4']) ? $body['z4'] : null;
        $this->_ccNumber = isset($body['b1']) ? $body['b1'] : null;
        $this->_ccExpMonth = isset($body['b3']) ? $body['b3'] : null;
        $this->_ccExpYear = isset($body['b4']) ? $body['b4'] : null;
        $this->_ccOwner = isset($body['c1']) ? $body['c1'] : null;

        //3D Params:
        $this->_3dsAcsurl = isset($body['3ds_acsurl']) ? $body['3ds_acsurl'] : null;
        $this->_3dsCavv = isset($body['3ds_cavv']) ? $body['3ds_cavv'] : null;
        $this->_3dsEci = isset($body['3ds_eci']) ? $body['3ds_eci'] : null;
        $this->_3dsStatus = isset($body['3ds_status']) ? $body['3ds_status'] : null;
        $this->_3dsTrxid = isset($body['3ds_trxid']) ? $body['3ds_trxid'] : null;

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
     * @return bool
     */
    protected function getErrorReason()
    {
        $body = $this->getBody();
        if (is_array($body)) {
            if (!empty($body['z3'])) {
                return $body['z3'];
            }
            if ($this->_credoraxConfig->is3dSecureEnabled() && isset($body['3ds_status']) && in_array($body['3ds_status'], ['N','U'])) {
                return $this->get3dStatusMessage($body['3ds_status']);
            }
        }
        return false;
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

        if ($this->_credoraxConfig->is3dSecureEnabled() && !(isset($body['3ds_status']) && (in_array($body['3ds_status'], ['Y','A']) || ($body['3ds_status'] === 'C' && isset($body['3ds_acsurl']) && $body['3ds_acsurl'])))) {
            return false;
        }
        if (isset($body['z2']) && $body['z2'] && (!isset($body['3ds_status']) || $body['3ds_status'] !== 'C')) {
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

        $this->_orderPayment->setTransactionAdditionalInfo(
            OrderTransaction::RAW_DETAILS,
            $body
        );

        $this->_orderPayment->setAdditionalInformation(
            CredoraxMethod::KEY_CREDORAX_LAST_OPERATION_CODE,
            $this->_operationCode
        );

        if ($this->_transactionId) {
            $this->_orderPayment->setAdditionalInformation(
                CredoraxMethod::TRANSACTION_ID,
                $this->_transactionId
            );
        }

        if ($this->_responseId) {
            $this->_orderPayment->setAdditionalInformation(
                CredoraxMethod::TRANSACTION_RESPONSE_ID,
                $this->_responseId
            );
        }

        if ($this->_authCode) {
            $this->_orderPayment->setAdditionalInformation(
                CredoraxMethod::TRANSACTION_AUTH_CODE_KEY,
                $this->_authCode
            );
        }

        if ($this->_3dsCavv) {
            $this->_orderPayment->setAdditionalInformation(
                CredoraxMethod::KEY_CREDORAX_3DS_CAVV,
                $this->_3dsCavv
            );
        }

        if ($this->_3dsEci) {
            $this->_orderPayment->setAdditionalInformation(
                CredoraxMethod::KEY_CREDORAX_3DS_ECI,
                $this->_3dsEci
            );
        }

        if ($this->_3dsStatus) {
            $this->_orderPayment->setAdditionalInformation(
                CredoraxMethod::KEY_CREDORAX_3DS_STATUS,
                $this->_3dsStatus
            );
        }

        if ($this->_3dsTrxid) {
            $this->_orderPayment->setAdditionalInformation(
                CredoraxMethod::KEY_CREDORAX_3DS_TRXID,
                $this->_3dsTrxid
            );
        }

        $this->_orderPayment->getMethodInstance()->getInfoInstance()->addData(
            [
                'cc_last_4' => substr($this->getCcNumber(), -4),
                'cc_number' => $this->getCcNumber(),
                'cc_exp_month' => $this->getCcExpMonth(),
                'cc_exp_year' => $this->getCcExpYear(),
                'cc_owner' => $this->getCcOwner(),
            ]
        );

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
     * @return int
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

    /**
     * @return string
     */
    public function getCcNumber()
    {
        return $this->_ccNumber;
    }

    /**
     * @return string
     */
    public function getCcExpMonth()
    {
        return $this->_ccExpMonth;
    }

    /**
     * @return string
     */
    public function getCcExpYear()
    {
        return $this->_ccExpYear;
    }

    /**
     * @return string
     */
    public function getCcOwner()
    {
        return $this->_ccOwner;
    }

    /**
     * @return string
     */
    public function get3dsAcsurl()
    {
        return $this->_3dsAcsurl;
    }

    /**
     * @return string
     */
    public function get3dsCavv()
    {
        return $this->_3dsCavv;
    }

    /**
     * @return string
     */
    public function get3dsEci()
    {
        return $this->_3dsEci;
    }

    /**
     * @return string
     */
    public function get3dsStatus()
    {
        return $this->_3dsStatus;
    }

    /**
     * @return string
     */
    public function get3dsTrxid()
    {
        return $this->_3dsTrxid;
    }

    /**
     * @return bool
     */
    public function is3dsChallengeRequired()
    {
        return $this->_credoraxConfig->is3dSecureEnabled() && $this->get3dsStatus() === 'C';
    }

    public function get3dStatusMessage()
    {
        return $this->_credoraxConfig->get3dStatusMessage($this->_3dsStatus);
    }
}
