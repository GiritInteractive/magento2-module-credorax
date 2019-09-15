<?php

namespace Credorax\Credorax\Model\Request\Payment;

use Credorax\Credorax\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Credorax\Credorax\Model\RequestInterface;
use Credorax\Credorax\Model\Response\AbstractPayment as AbstractPaymentResponse;

/**
 * Credorax SaleTokenization payment request model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class SaleTokenization extends Sale implements RequestInterface
{
    /**
     * Credorax Operation Code
     * @var integer
     */
    const CREDORAX_O = 23;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return AbstractPaymentRequest::PAYMENT_SALE_TOKENIZATION_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractPaymentResponse::PAYMENT_SALE_TOKENIZATION_HANDLER;
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
            ]
        );
    }
}
