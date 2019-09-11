<?php

namespace Credorax\Credorax\Model\Request\Gateway;

use Credorax\Credorax\Model\Request\AbstractGateway as AbstractGatewayRequest;
use Credorax\Credorax\Model\RequestInterface;
use Credorax\Credorax\Model\Response\AbstractGateway as AbstractGatewayResponse;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Credorax Credorax capture gateway request model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Capture extends AbstractGatewayRequest implements RequestInterface
{
    /**
     * Credorax Operation Code
     * @var integer
     */
    const CREDORAX_O = 3;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return AbstractGatewayRequest::GATEWAY_CAPTURE_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractGatewayResponse::GATEWAY_CAPTURE_HANDLER;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws \Magento\Framework\Exception\PaymentException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getParams()
    {
        /** @var OrderPayment $orderPayment */
        $orderPayment = $this->orderPayment;

        /** @var Order $order */
        $order = $orderPayment->getOrder();

        return array_replace_recursive(
            parent::getParams(),
            [
                'O' => self::CREDORAX_O,
            ]
        );
    }
}
