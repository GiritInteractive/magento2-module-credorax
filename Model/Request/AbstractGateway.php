<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Request;

use Shift4\Shift4\Lib\Http\Client\Curl;
use Shift4\Shift4\Model\AbstractRequest;
use Shift4\Shift4\Model\Config;
use Shift4\Shift4\Model\Shift4Method;
use Shift4\Shift4\Model\Request\Factory as RequestFactory;
use Shift4\Shift4\Model\Response\Factory as ResponseFactory;
use Shift4\Shift4\Model\ResponseInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Shift4 Shift4 abstract gateway request model.
 */
abstract class AbstractGateway extends AbstractRequest
{
    /**
     * Gateway methods.
     */
    const GATEWAY_CAPTURE_METHOD = 'gateway_capture';
    const GATEWAY_REFUND_METHOD = 'gateway_refund';
    const GATEWAY_VOID_METHOD = 'gateway_cancel';

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var OrderPayment
     */
    protected $orderPayment;

    /**
     * @var float
     */
    protected $amount;

    /**
     * AbstractPayment constructor.
     *
     * @param Config                $config
     * @param Curl                  $curl
     * @param RequestFactory        $requestFactory
     * @param ResponseFactory       $responseFactory
     * @param OrderPayment|null     $orderPayment
     * @param float|null            $amount
     */
    public function __construct(
        Config $shift4Config,
        Curl $curl,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        OrderPayment $orderPayment,
        $amount = 0.0
    ) {
        parent::__construct(
            $shift4Config,
            $curl,
            $responseFactory
        );

        $this->requestFactory = $requestFactory;
        $this->orderPayment = $orderPayment;
        $this->amount = $amount;
    }

    /**
     * {@inheritdoc}
     *
     * @return ResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getResponseHandler()
    {
        $responseHandler = $this->_responseFactory->create(
            $this->getResponseHandlerType(),
            $this->_curl,
            $this->orderPayment
        );

        return $responseHandler;
    }

    /**
     * Return full endpoint to particular method for request call.
     *
     * @return string
     */
    protected function getEndpoint()
    {
        return $this->_shift4Config->getShift4GatewayUrl();
    }

    /**
     * Return request params.
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
            $this->getOrderData($order),
            [
                'g2' => $orderPayment->getAdditionalInformation(Shift4Method::KEY_CREDORAX_RESPONSE_ID),
                'g3' => $orderPayment->getAdditionalInformation(Shift4Method::KEY_CREDORAX_AUTH_CODE),
                'a4' => $this->amountFormat($this->amount, $order->getBaseCurrencyCode()),
            ]
        );
    }
}
