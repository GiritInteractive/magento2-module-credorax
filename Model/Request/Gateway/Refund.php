<?php

namespace Credorax\Credorax\Model\Request\Gateway;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\Config;
use Credorax\Credorax\Model\Request\AbstractGateway as AbstractGatewayRequest;
use Credorax\Credorax\Model\Request\Factory as RequestFactory;
use Credorax\Credorax\Model\RequestInterface;
use Credorax\Credorax\Model\Response\AbstractGateway as AbstractGatewayResponse;
use Credorax\Credorax\Model\Response\Factory as ResponseFactory;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Credorax Credorax refund gateway request model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Refund extends AbstractGatewayRequest implements RequestInterface
{
    /**
     * Credorax Operation Code
     * @var integer
     */
    const CREDORAX_O = 5;

    /**
     * @var TransactionRepositoryInterface
     */
    protected $transactionRepository;

    /**
     * Refund constructor.
     *
     * @param Config                         $config
     * @param Curl                           $curl
     * @param RequestFactory                 $requestFactory
     * @param ResponseFactory                $responseFactory
     * @param OrderPayment                   $orderPayment
     * @param TransactionRepositoryInterface $transactionRepository
     * @param float                          $amount
     */
    public function __construct(
        Config $credoraxConfig,
        Curl $curl,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        OrderPayment $orderPayment,
        TransactionRepositoryInterface $transactionRepository,
        $amount = 0.0
    ) {
        parent::__construct(
            $credoraxConfig,
            $curl,
            $requestFactory,
            $responseFactory,
            $orderPayment,
            $amount
        );

        $this->transactionRepository = $transactionRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getRequestMethod()
    {
        return AbstractGatewayRequest::GATEWAY_REFUND_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractGatewayResponse::GATEWAY_REFUND_HANDLER;
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
