<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Model;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\Response\Factory as ResponseFactory;
use Magento\Framework\Exception\PaymentException;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Model\Order;

/**
 * Credorax Credorax abstract request model.
 */
abstract class AbstractRequest extends AbstractApi
{
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
    abstract protected function getEndpoint();

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
        $params =  [
            'M' => $this->_credoraxConfig->getMerchantId()
        ];

        if (
            ($subMerchantId = (int)$this->_credoraxConfig->getSubMerchantId()) &&
            strlen((string)$subMerchantId) <= 15
        ) {
            $params['h3'] = $subMerchantId;
        }

        if (($billingDescriptor = $this->_credoraxConfig->getBillingDescriptor())) {
            $params['i2'] = $billingDescriptor;
        }

        return $params;
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

        foreach ($params as &$val) {
            $val = $this->fixUTF8($val);
        }

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
        //$preparedURL = $this->_curl->buildQuery($endpoint, $params);

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Content-Length' => \strlen(http_build_query($params))
        ];
        $this->_curl->setHeaders($headers);

        $this->_credoraxConfig->log('AbstractPaymentRequest::sendRequest() ', 'debug', [
            'method' => $this->getRequestMethod(),
            'request' => [
                'Type' => 'POST',
                'Endpoint' => $endpoint,
                'Headers' => $headers,
                'Params' => $params,
                //'PreparedURL' => $preparedURL,
            ],
        ]);

        $this->_curl->post($endpoint, $params);

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


    protected function setLog($data)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/custom.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info($data);
    }
    /**
     * @param Order $order
     * @param bool $includeBilling
     *
     * @return array
     */
    protected function getOrderData(Order $order)
    {
        $createdAt = $order->getCreatedAt();

        $orderData = [
            'a1' => $order->getIncrementId() . (int)round(microtime(true) * 10000),
            'h9' => $order->getIncrementId(),
            'a4' => $this->amountFormat($order->getBaseGrandTotal(), $order->getBaseCurrencyCode()),
            'a5' => $order->getBaseCurrencyCode(),
            'a6' => $createdAt ? date('ymd', strtotime($createdAt)) : '',
            'a7' => $createdAt ? date('His', strtotime($createdAt)) : '',
        ];

        return $orderData;
    }

    /**
     * @param Order $order
     *
     * @return array
     */
    protected function getBillingData(Order $order)
    {
        /** @var OrderPayment $orderPayment */
        $orderPayment = $order->getPayment();

        /** @var OrderAddressInterface $billing */
        $billing = $order->getBillingAddress();

        $data = [
            'b2' => $this->getCcTypeNumberByCode($orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CC_TYPE)),
            'c1' => $this->fixUTF8($orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CC_OWNER)),
        ];

        if ($billing !== null) {
            $data = array_merge($data, [
                'c2' => (int) substr(preg_replace('/[^\d]/', '', $billing->getTelephone()), 0, 15),
                'c3' => $this->fixUTF8($billing->getEmail()),
                'c5' => preg_replace('/(?i)[^\d\p{L}\p{C}À-ÿ_]/', '-', $this->fixUTF8(is_array($billing->getStreet()) ? implode(' ', $billing->getStreet()) : '')),
                'c7' => preg_replace('/(?i)[^\p{L}\p{C}À-ÿ_]/', '-', $this->fixUTF8($billing->getCity())),
                'c9' => $billing->getCountryId(),
                'c10' => preg_replace('/[\W_]/', '', $this->fixUTF8($billing->getPostcode())),
            ]);
        }

        return $data;
    }
}
