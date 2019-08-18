<?php

namespace Credorax\Credorax\Model\Response\Payment;

use Credorax\Credorax\Model\CredoraxMethod;
use Credorax\Credorax\Model\Response\AbstractPayment;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax payment capture response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Capture extends AbstractPayment implements ResponseInterface
{
    /**
     * @var int
     */
    protected $transactionId;

    /**
     * @var string
     */
    protected $authCode;

    /**
     * @return Capture
     */
    protected function processResponseData()
    {
        $body = $this->getBody();

        $this->transactionId = $body['transactionId'];
        $this->authCode = $body['authCode'];

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
        if (strtolower($body['transactionStatus']) === 'error') {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @return string
     */
    public function getAuthCode()
    {
        return $this->authCode;
    }

    /**
     * @return Capture
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateTransaction()
    {
        parent::updateTransaction();

        if ($this->_credoraxConfig->getPaymentAction() === CredoraxMethod::ACTION_AUTHORIZE_CAPTURE) {
            $this->orderPayment->setAdditionalInformation(
                CredoraxMethod::TRANSACTION_AUTH_CODE_KEY,
                $this->getAuthCode()
            );
            $this->orderPayment->setAdditionalInformation(
                CredoraxMethod::TRANSACTION_ID,
                $this->getTransactionId()
            );
        }

        $this->orderPayment
            ->setParentTransactionId($this->orderPayment->getTransactionId())
            ->setTransactionId($this->getTransactionId())
            ->setIsTransactionClosed(1);

        return $this;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            [
                'transactionId',
                'authCode',
                'transactionStatus',
            ]
        );
    }
}
