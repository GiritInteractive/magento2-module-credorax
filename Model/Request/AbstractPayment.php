<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

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
 * Credorax Credorax abstract payment request model.
 */
abstract class AbstractPayment extends AbstractRequest
{
    /**
     * Payment methods.
     */
    const PAYMENT_AUTH_METHOD = 'payment_auth';
    const PAYMENT_AUTH_TOKENIZATION_METHOD = 'payment_auth_tokenization';
    const PAYMENT_AUTH_USE_TOKEN_METHOD = 'payment_auth_use_token';
    const PAYMENT_SALE_METHOD = 'payment_sale';
    const PAYMENT_SALE_TOKENIZATION_METHOD = 'payment_sale_tokenization';
    const PAYMENT_SALE_USE_TOKEN_METHOD = 'payment_sale_use_token';

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
     * AbstractGateway constructor.
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
        return $this->_credoraxConfig->getCredoraxPaymentUrl();
    }

    /**
     * Return 3d secure related request params.
     *
     * @return array
     */
    protected function get3dSecureParams(OrderPayment $orderPayment)
    {
        $params = [
            //'3ds_initiate' => '02' // Do not send this param if 3D secure is disabled.
        ];
        if ($this->_credoraxConfig->is3dSecureEnabled()) {
            if ($this->_credoraxConfig->isUsingSmart3d()) {
                $params['3ds_initiate'] = '03';
            } else {
                $params['3ds_initiate'] = '01';
            }
            $params['3ds_browseracceptheader'] = '3dsBrowseracceptheader';
            $params['3ds_browsercolordepth'] = '32';
            $params['3ds_browserscreenheight'] = '123';
            $params['3ds_browserscreenwidth'] = '1';
            $params['3ds_browserjavaenabled'] = 'false';
            $params['3ds_browsertz'] = '1';
            $params['3ds_challengewindowsize'] = '03';
            $params['3ds_compind'] = $orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_3DS_COMPIND) ?: 'N';
            $params['3ds_transtype'] = '01';
            $params['3ds_purchasedate'] = date('YmdHis', strtotime($orderPayment->getOrder()->getCreatedAt()));
            $params['3ds_redirect_url'] = $this->_credoraxConfig->getUrlBuilder()->getUrl('credorax/payment_challenge/callback', ['quote' => $orderPayment->getOrder()->getQuoteId()]);
            $params['d6'] = 'English';
        }
        return $params;
    }

    /**
     * Return request params.
     *
     * @return array
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
            $this->getBillingData($order),
            $this->get3dSecureParams($orderPayment)
        );
    }
}
