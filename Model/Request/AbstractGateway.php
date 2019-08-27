<?php

namespace Credorax\Credorax\Model\Request;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\AbstractRequest;
use Credorax\Credorax\Model\Config;
use Credorax\Credorax\Model\CredoraxMethod;
use Credorax\Credorax\Model\Request\Factory as RequestFactory;
use Credorax\Credorax\Model\Response\Factory as ResponseFactory;
use Credorax\Credorax\Model\ResponseInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Credorax Credorax abstract gateway request model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
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
        Config $credoraxConfig,
        Curl $curl,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        OrderPayment $orderPayment,
        $amount = 0.0
    ) {
        parent::__construct(
            $credoraxConfig,
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
        return $this->_credoraxConfig->getCredoraxGatewayUrl();
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
                'g3' => $orderPayment->getAdditionalInformation(CredoraxMethod::TRANSACTION_AUTH_CODE_KEY),
                'a4' => $this->amountFormat($this->amount, $order->getBaseCurrencyCode()),
            ]
        );
    }
}
