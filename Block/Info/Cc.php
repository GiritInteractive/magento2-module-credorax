<?php

namespace Credorax\Credorax\Block\Info;

use Credorax\Credorax\Model\CredoraxMethod;
use Magento\Framework\App\State;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\Config;

class Cc extends \Magento\Payment\Block\Info\Cc
{
    /**
     * @var State
     */
    protected $appState;

    /**
     * @method __construct
     * @param  Context     $context
     * @param  Config      $paymentConfig
     * @param  State       $appState
     * @param  array       $data
     */
    public function __construct(
        Context $context,
        Config $paymentConfig,
        State $appState,
        array $data = []
    ) {
        parent::__construct($context, $paymentConfig, $data);
        $this->appState = $appState;
    }

    /**
     * Prepare credit card related payment info
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];

        $info = $this->getInfo();
        if ($ccType = $this->getCcTypeName()) {
            $data[(string)__('Credit Card Type')] = $ccType;
        }
        if ($this->getInfo()->getCcLast4()) {
            $data[(string)__('Credit Card Number')] = sprintf('xxxx-%s', $this->getInfo()->getCcLast4());
        }
        if (($expMonth = $info->getCcExpMonth()) && ($expYear = $info->getCcExpYear())) {
            $data[(string)__('Card expiration date')] = $expMonth . '/' . $expYear;
        }
        if (($ccOwner = $info->getAdditionalInformation(CredoraxMethod::KEY_CC_OWNER))) {
            $data[(string)__('Card holder name')] = $ccOwner;
        }
        if (($transactionId = $info->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_TRANSACTION_ID))) {
            $data[(string)__('Transaction Id (RRN)')] = $transactionId;
        }
        if (($trxid = $info->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_3DS_TRXID))) {
            $data[(string)__('3DS transaction ID')] = $trxid;
        }
        if ($this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_ADMINHTML) {
            if (($eci = $info->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_3DS_VERSION))) {
                $data[(string)__('3DS version')] = $eci;
            }
            if (($eci = $info->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_3DS_ECI))) {
                $data[(string)__('3DS ECI')] = $eci;
            }
            if (($authCode = $info->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_AUTH_CODE))) {
                $data[(string)__('Auth code')] = $authCode;
            }
            if (($responseId = $info->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_RESPONSE_ID))) {
                $data[(string)__('Response ID')] = $responseId;
            }
            if (($riskScore = $info->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_RISK_SCORE))) {
                $data[(string)__('Risk score')] = $riskScore;
            }
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
