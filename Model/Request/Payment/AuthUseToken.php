<?php

namespace Credorax\Credorax\Model\Request\Payment;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\Config;
use Credorax\Credorax\Model\CredoraxMethod;
use Credorax\Credorax\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Credorax\Credorax\Model\Request\Factory as RequestFactory;
use Credorax\Credorax\Model\RequestInterface;
use Credorax\Credorax\Model\Response\AbstractPayment as AbstractPaymentResponse;
use Credorax\Credorax\Model\Response\Factory as ResponseFactory;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Credorax AuthUseToken payment request model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class AuthUseToken extends Auth implements RequestInterface
{
    /**
     * Credorax Operation Code
     * @var integer
     */
    const CREDORAX_O = 12;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @method __construct
     * @param  Config                          $credoraxConfig
     * @param  Curl                            $curl
     * @param  RequestFactory                  $requestFactory
     * @param  ResponseFactory                 $responseFactory
     * @param  OrderPayment                    $orderPayment
     * @param  float                           $amount
     * @param  PaymentTokenManagementInterface $paymentTokenFactory
     */
    public function __construct(
        Config $credoraxConfig,
        Curl $curl,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        OrderPayment $orderPayment,
        $amount = 0.0,
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
        $token = $this->paymentTokenManagement->getByPublicHash(
            $this->orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CC_TOKEN),
            $this->orderPayment->getOrder()->getCustomerId()
        );

        return array_replace_recursive(
            parent::getParams(),
            [
                'O' => self::CREDORAX_O,
                'g1' => $token->getGatewayToken(),
            ]
        );
    }
}
