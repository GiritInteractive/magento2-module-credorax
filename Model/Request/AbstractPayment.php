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
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Shift4 Shift4 abstract payment request model.
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
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * AbstractGateway constructor.
     *
     * @param Config                 $config
     * @param Curl                   $curl
     * @param RequestFactory         $requestFactory
     * @param ResponseFactory        $responseFactory
     * @param OrderPayment|null      $orderPayment
     * @param float|null             $amount
     * @param TimezoneInterface|null $timezoneInterface
     */
    public function __construct(
        Config $shift4Config,
        Curl $curl,
        RequestFactory $requestFactory,
        ResponseFactory $responseFactory,
        OrderPayment $orderPayment,
        TimezoneInterface $timezoneInterface,
        $amount = 0.0,
    ) {
        parent::__construct(
            $shift4Config,
            $curl,
            $responseFactory
        );

        $this->requestFactory = $requestFactory;
        $this->requestFactory = $requestFactory;
        $this->orderPayment = $orderPayment;
        $this->timezoneInterface = $timezoneInterface;
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
        return $this->_shift4Config->getShift4PaymentUrl();
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
        if ($this->_shift4Config->is3dSecureEnabled()) {
            if ($this->_shift4Config->isUsingSmart3d()) {
                $params['3ds_initiate'] = '03';
            } else {
                $params['3ds_initiate'] = '01';
            }
            if (($billing = $orderPayment->getOrder()->getBillingAddress()) !== null && (int) $billing->getTelephone() && $billing->getCountryId()) {
                $params['3ds_homephonecountry'] = $this->countryCodeToPhoneCode($billing->getCountryId());
            }
            $params['3ds_browseracceptheader'] = '3dsBrowseracceptheader';
            $params['3ds_browsercolordepth'] = '32';
            $params['3ds_browserscreenheight'] = '123';
            $params['3ds_browserscreenwidth'] = '1';
            $params['3ds_browserjavaenabled'] = 'false';
            $params['3ds_browsertz'] = '1';
            $params['3ds_challengewindowsize'] = '03';
            $params['3ds_compind'] = $orderPayment->getAdditionalInformation(Shift4Method::KEY_SHIFT4_3DS_COMPIND) ?: 'N';
            $params['3ds_transtype'] = '01';
            $params['3ds_purchasedate'] = date('YmdHis', strtotime($this->timezoneInterface->date()->format('Y-m-d H:i:s')));
            $params['3ds_redirect_url'] = $this->_shift4Config->getUrlBuilder()->getUrl('shift4/payment_challenge/callback', ['quote' => $orderPayment->getOrder()->getQuoteId()]);
            $params['d6'] = $orderPayment->getAdditionalInformation(Shift4Method::KEY_SHIFT4_BROWSER_LANG) ?: 'en-US';
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
