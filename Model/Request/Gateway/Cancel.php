<?php

namespace Credorax\Credorax\Model\Request\Gateway;

use Credorax\Credorax\Model\Request\AbstractGateway as AbstractGatewayRequest;
use Credorax\Credorax\Model\RequestInterface;
use Credorax\Credorax\Model\Response\AbstractGateway as AbstractGatewayResponse;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Credorax Credorax void gateway request model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Cancel extends AbstractGatewayRequest implements RequestInterface
{
    /**
     * Credorax Operation Code
     * @var integer
     */
    const CREDORAX_O = 9;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return AbstractGatewayRequest::GATEWAY_VOID_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractGatewayResponse::GATEWAY_VOID_HANDLER;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
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
                'a4' => $this->amountFormat($order->getBaseGrandTotal(), $order->getBaseCurrencyCode()),
            ]
        );
    }
}
