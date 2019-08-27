<?php

namespace Credorax\Credorax\Model\Request\Payment;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\Config;
use Credorax\Credorax\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Credorax\Credorax\Model\Request\Factory as RequestFactory;
use Credorax\Credorax\Model\RequestInterface;
use Credorax\Credorax\Model\Response\AbstractPayment as AbstractPaymentResponse;
use Credorax\Credorax\Model\Response\Factory as ResponseFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Credorax Credorax sale payment request model.
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
     * @var PaymentTokenManagementInterface
     */
    protected $paymentTokenManagement;

    /**
     * Cc constructor.
     *
     * @param Config                          $config
     * @param Curl                            $curl
     * @param RequestFactory                  $requestFactory
     * @param ResponseFactory                 $responseFactory
     * @param OrderPayment|null               $orderPayment
     * @param float|null                      $amount
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        Config $credoraxConfig,
        Curl $curl,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        $orderPayment,
        $amount,
        PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        parent::__construct(
            $credoraxConfig,
            $curl,
            $requestFactory,
            $responseFactory,
            $orderPayment,
            $amount
        );

        $this->paymentTokenManagement = $paymentTokenManagement;
    }

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
