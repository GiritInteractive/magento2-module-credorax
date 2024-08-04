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

use Shift4\Shift4\Lib\Http\Client\Curl;
use Shift4\Shift4\Model\Config;
use Shift4\Shift4\Model\Request\AbstractGateway as AbstractGatewayRequest;
use Shift4\Shift4\Model\Request\Factory as RequestFactory;
use Shift4\Shift4\Model\RequestInterface;
use Shift4\Shift4\Model\Response\AbstractGateway as AbstractGatewayResponse;
use Shift4\Shift4\Model\Response\Factory as ResponseFactory;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Shift4 Shift4 refund gateway request model.
 */
class Refund extends AbstractGatewayRequest implements RequestInterface
{
    /**
     * Shift4 Operation Code
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
        Config $shift4Config,
        Curl $curl,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        OrderPayment $orderPayment,
        TransactionRepositoryInterface $transactionRepository,
        $amount = 0.0
    ) {
        parent::__construct(
            $shift4Config,
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
