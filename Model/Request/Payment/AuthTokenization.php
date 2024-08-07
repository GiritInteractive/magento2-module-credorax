<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Request\Payment;

use Shift4\Shift4\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Shift4\Shift4\Model\RequestInterface;
use Shift4\Shift4\Model\Response\AbstractPayment as AbstractPaymentResponse;

/**
 * Shift4 AuthTokenization payment request model.
 */
class AuthTokenization extends Auth implements RequestInterface
{
    /**
     * Shift4 Operation Code
     * @var integer
     */
    const SHIFT4O = 28;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return AbstractPaymentRequest::PAYMENT_AUTH_TOKENIZATION_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractPaymentResponse::PAYMENT_AUTH_TOKENIZATION_HANDLER;
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
                'O' => self::SHIFT4O,
            ]
        );
    }
}
