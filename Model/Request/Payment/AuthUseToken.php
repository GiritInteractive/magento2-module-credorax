<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Request\Payment;

use Shift4\Shift4\Lib\Http\Client\Curl;
use Shift4\Shift4\Model\Config;
use Shift4\Shift4\Model\Shift4Method;
use Shift4\Shift4\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Shift4\Shift4\Model\Request\Factory as RequestFactory;
use Shift4\Shift4\Model\RequestInterface;
use Shift4\Shift4\Model\Response\AbstractPayment as AbstractPaymentResponse;
use Shift4\Shift4\Model\Response\Factory as ResponseFactory;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Shift4 AuthUseToken payment request model.
 */
class AuthUseToken extends Auth implements RequestInterface
{
    /**
     * Shift4 Operation Code
     * @var integer
     */
    const CREDORAX_O = 12;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @method __construct
     * @param  Config                          $shift4Config
     * @param  Curl                            $curl
     * @param  RequestFactory                  $requestFactory
     * @param  ResponseFactory                 $responseFactory
     * @param  OrderPayment                    $orderPayment
     * @param  float                           $amount
     * @param  TimezoneInterface               $timezoneInterface
     * @param  PaymentTokenManagementInterface $paymentTokenFactory

     */
    public function __construct(
        Config $shift4Config,
        Curl $curl,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        OrderPayment $orderPayment,
        TimezoneInterface $timezoneInterface,
        PaymentTokenManagementInterface $paymentTokenManagement,
        $amount = 0.0,
    ) {
        parent::__construct(
            $shift4Config,
            $curl,
            $requestFactory,
            $responseFactory,
            $orderPayment,
            $timezoneInterface,
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
            $this->orderPayment->getAdditionalInformation(Shift4Method::KEY_CC_TOKEN),
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
