<?php

namespace Credorax\Credorax\Model\Request\Payment;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\AbstractRequest;
use Credorax\Credorax\Model\AbstractResponse;
use Credorax\Credorax\Model\Config;
use Credorax\Credorax\Model\Request\AbstractPayment;
use Credorax\Credorax\Model\Request\Factory as PaymentFactory;
use Credorax\Credorax\Model\Request\Payment\Factory as PaymentRequestFactory;
use Credorax\Credorax\Model\RequestInterface;
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
class Sale extends AbstractPayment implements RequestInterface
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
     * @param PaymentFactory                  $requestFactory
     * @param Factory                         $paymentRequestFactory
     * @param ResponseFactory                 $responseFactory
     * @param OrderPayment|null               $orderPayment
     * @param float|null                      $amount
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     */
    public function __construct(
        Config $credoraxConfig,
        Curl $curl,
        PaymentFactory $requestFactory,
        PaymentRequestFactory $paymentRequestFactory,
        ResponseFactory $responseFactory,
        $orderPayment,
        $amount,
        PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        parent::__construct(
            $credoraxConfig,
            $curl,
            $requestFactory,
            $paymentRequestFactory,
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
        return AbstractRequest::PAYMENT_SALE_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractResponse::PAYMENT_SALE_HANDLER;
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
