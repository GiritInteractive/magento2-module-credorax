<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Request\Gateway;

use Shift4\Shift4\Model\Request\AbstractGateway as AbstractGatewayRequest;
use Shift4\Shift4\Model\RequestInterface;
use Shift4\Shift4\Model\Response\AbstractGateway as AbstractGatewayResponse;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Shift4 Shift4 capture gateway request model.
 */
class Capture extends AbstractGatewayRequest implements RequestInterface
{
    /**
     * Shift4 Operation Code
     * @var integer
     */
    const SHIFT4O = 3;

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
                'O' => self::SHIFT4O,
            ]
        );
    }
}
