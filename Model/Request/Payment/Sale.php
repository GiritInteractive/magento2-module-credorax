<?php

namespace Credorax\Credorax\Model\Request\Payment;

use Credorax\Credorax\Model\CredoraxMethod;
use Credorax\Credorax\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Credorax\Credorax\Model\RequestInterface;
use Credorax\Credorax\Model\Response\AbstractPayment as AbstractPaymentResponse;

/**
 * Credorax Sale payment request model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Sale extends AbstractPaymentRequest implements RequestInterface
{
    /**
     * Credorax Operation Code
     * @var integer
     */
    const CREDORAX_O = 1;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return AbstractPaymentRequest::PAYMENT_SALE_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractPaymentResponse::PAYMENT_SALE_HANDLER;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getParams()
    {
        return array_replace_recursive(
            parent::getParams(),
            [
                'O' => self::CREDORAX_O,
                'PKey' => $this->orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_PKEY),
            ]
        );
    }
}
