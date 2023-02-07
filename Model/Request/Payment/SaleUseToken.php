<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Credorax SaleUseToken payment request model.
 */
class SaleUseToken extends Sale implements RequestInterface
{
    /**
     * Credorax Operation Code
     * @var integer
     */
    const CREDORAX_O = 11;

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
     * @param  TimezoneInterface               $timezoneInterface
     * @param  PaymentTokenManagementInterface $paymentTokenFactory
     */
    public function __construct(
        Config $credoraxConfig,
        Curl $curl,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        OrderPayment $orderPayment,
        $amount = 0.0,
        TimezoneInterface $timezoneInterface,
        PaymentTokenManagementInterface $paymentTokenManagement
    ) {
        parent::__construct(
            $credoraxConfig,
            $curl,
            $requestFactory,
            $responseFactory,
            $orderPayment,
            $amount,
            $timezoneInterface
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
        return AbstractPaymentRequest::PAYMENT_SALE_USE_TOKEN_METHOD;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getResponseHandlerType()
    {
        return AbstractPaymentResponse::PAYMENT_SALE_USE_TOKEN_HANDLER;
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
