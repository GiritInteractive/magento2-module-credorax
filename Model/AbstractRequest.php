<?php

namespace Credorax\Credorax\Model;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\Response\Factory as ResponseFactory;
use Magento\Framework\Exception\PaymentException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order;

/**
 * Credorax Credorax abstract request model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
abstract class AbstractRequest extends AbstractApi
{
    /**
     * Payment gateway methods.
     */
    const PAYMENT_SALE_METHOD = 'payment_sale';
    const PAYMENT_AUTH_METHOD = 'payment_auth';
    const PAYMENT_CAPTURE_METHOD = 'payment_capture';
    const PAYMENT_REFUND_METHOD = 'payment_refund';
    const PAYMENT_VOID_METHOD = 'payment_cancel';

    /**
     * @var Curl
     */
    protected $_curl;

    /**
     * @var ResponseInterface
     */
    protected $_responseFactory;

    /**
     * Object constructor.
     *
     * @param Config          $credoraxConfig
     * @param Curl            $curl
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        Config $credoraxConfig,
        Curl $curl,
        ResponseFactory $responseFactory
    ) {
        parent::__construct(
            $credoraxConfig
        );

        $this->_curl = $curl;
        $this->_responseFactory = $responseFactory;
    }

    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws PaymentException
     */
    public function process()
    {
        $this->sendRequest();

        return $this
            ->getResponseHandler()
            ->process();
    }

    /**
     * Return full endpoint to particular method for request call.
     *
     * @return string
     */
    protected function getEndpoint()
    {
        return $this->_credoraxConfig->getCredoraxGatewayUrl() . $this->getRequestMethod();
    }

    /**
     * Return method for request call.
     *
     * @return string
     */
    abstract protected function getRequestMethod();

    /**
     * Return response handler type.
     *
     * @return string
     */
    abstract protected function getResponseHandlerType();

    /**
     * Return request params.
     *
     * @return array
     */
    protected function getParams()
    {
        return [
            'M' => $this->_credoraxConfig->getMerchantId()
        ];
    }

    /**
     * @return array
     * @throws PaymentException
     */
    protected function prepareParams()
    {
        $params = array_filter($this->getParams(), function ($value) {
            return !is_null($value) && $value !== '';
        });

        //= Add the K param (SignatureKey|SHA256 Cipher)
        ksort($params);
        $cipherParams = preg_replace("/[\<|\>|\"|\'|\\|\(|\)]/", " ", $params);
        $cipherParams = array_map('trim', $cipherParams);
        $cipherString = implode("", $cipherParams);
        $cipherString .= $this->_credoraxConfig->getSignatureKey();
        $params['K'] = hash('sha256', $cipherString);

        return $params;
    }

    /**
     * @return AbstractRequest
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function sendRequest()
    {
        $endpoint = $this->getEndpoint();
        $params = $this->prepareParams();
        $preparedURL = $this->_curl->buildQuery($endpoint, $params);

        $this->_credoraxConfig->log('AbstractRequest::sendRequest() ', 'debug', [
            'method' => $this->getRequestMethod(),
            'request' => [
                'Type' => 'POST',
                'Endpoint' => $endpoint,
                'Params' => $params,
                'PreparedURL' => $preparedURL,
            ],
        ]);

        $this->_curl->post($preparedURL, []);

        return $this;
    }

    /**
     * Return proper response handler.
     *
     * @return ResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function getResponseHandler()
    {
        $responseHandler = $this->_responseFactory->create(
            $this->getResponseHandlerType(),
            $this->_curl
        );

        return $responseHandler;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    protected function getOrderData(Order $order)
    {
        /** @var OrderAddressInterface $billing */
        $billing = $order->getBillingAddress();

        /** @var OrderPayment $orderPayment */
        $orderPayment = $order->getPayment();

        $exponents = $this->getExponentsByCurrency($order->getBaseCurrencyCode());

        $orderData = [
            'a1' => $order->getIncrementId() . (microtime(true) * 10000),
            'h9' => $order->getIncrementId(),
            'a4' => number_format((float)$order->getBaseGrandTotal(), $exponents, '', ''),
            'a5' => $order->getBaseCurrencyCode(),
            'a6' => date('ymd', strtotime($order->getCreatedAt())),
            'a7' => date('His', strtotime($order->getCreatedAt())),
            'b2' => $this->getCcTypeNumberByCode($orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CC_TYPE)),
            'c1' => $orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CC_OWNER),
        ];

        if ($billing !== null) {
            $orderData = array_merge($orderData, [
                'c2' => preg_replace('/[^\d-]/', '', $billing->getTelephone()),
                'c3' => $billing->getEmail(),
                'c5' => preg_replace('/[\W_]/', '-', (is_array($billing->getStreet()) ? implode(' ', $billing->getStreet()) : '')),
                'c7' => preg_replace('/[^\p{L}]/', '-', $billing->getCity()),
                'c9' => $billing->getCountryId(),
                'c10' => preg_replace('/[\W_]/', '', $billing->getPostcode()),
            ]);
        }

        // TODO: Add billing descriptor if needed

        return $orderData;
    }
}
