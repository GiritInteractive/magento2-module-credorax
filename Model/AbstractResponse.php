<?php

namespace Credorax\Credorax\Model;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\PaymentException;

/**
 * Credorax Credorax abstract response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
abstract class AbstractResponse extends AbstractApi
{
    /**
     * Response result const.
     */
    const STATUS_SUCCESS = 1;
    const STATUS_FAILED = 2;

    /**
     * @var Curl
     */
    protected $_curl;

    /**
     * @var int
     */
    protected $_status;

    /**
     * @var array
     */
    protected $_headers;

    /**
     * @var array
     */
    protected $_body;

    /**
     * AbstractResponse constructor.
     *
     * @param Config $credoraxConfig
     * @param Curl   $curl
     */
    public function __construct(
        Config $credoraxConfig,
        Curl $curl
    ) {
        parent::__construct(
            $credoraxConfig
        );
        $this->_curl = $curl;
    }

    /**
     * @return AbstractResponse
     * @throws PaymentException
     */
    public function process()
    {
        $requestStatus = $this->getRequestStatus();

        $this->_credoraxConfig->log('AbstractResponse::process() ', 'debug', [
            'response' => $this->prepareResponseData(),
            'status' => $requestStatus === true
                ? self::STATUS_SUCCESS
                : self::STATUS_FAILED,
        ]);

        if ($requestStatus === false) {
            throw new PaymentException($this->getErrorMessage());
        }

        $this->validateResponseData();

        return $this;
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    protected function getErrorMessage()
    {
        $errorReason = $this->getErrorReason();
        if ($errorReason !== false && $this->_credoraxConfig->isDebugEnabled()) {
            return __('Request to payment gateway failed. Details: "%1".', $errorReason);
        }

        return __('Request to payment gateway failed.');
    }

    /**
     * @return bool
     */
    protected function getErrorReason()
    {
        $body = $this->getBody();
        if (is_array($body) && !empty($body['z3'])) {
            return $body['z3'];
        }
        return false;
    }

    /**
     * Determine if request succeed or failed.
     *
     * @return bool
     */
    protected function getRequestStatus()
    {
        $httpStatus = $this->getStatus();
        if (!in_array($httpStatus, [100, 200])) {
            return false;
        }

        return true;
    }

    /**
     * @return int
     */
    protected function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * @return int
     */
    protected function getStatus()
    {
        if ($this->_status === null) {
            $this->_status = $this->_curl->getStatus();
        }

        return $this->_status;
    }

    /**
     * @return array
     */
    protected function getHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = $this->_curl->getHeaders();
        }

        return $this->_headers;
    }

    /**
     * @return array
     */
    protected function getBody()
    {
        if ($this->_body === null) {
            $body = $this->_curl->getBody();
            $this->_body = json_decode($body, 1);
            if ($body && !$this->_body) {
                parse_str($body, $this->_body);
            }
        }

        return $this->_body;
    }

    /**
     * @return array
     */
    protected function prepareResponseData()
    {
        return [
            'Status' => $this->getStatus(),
            'Headers' => $this->getHeaders(),
            'Body' => $this->getBody(),
        ];
    }

    /**
     * @return AbstractResponse
     * @throws PaymentException
     */
    protected function validateResponseData()
    {
        $requiredKeys = $this->getRequiredResponseDataKeys();
        $bodyKeys = array_keys($this->getBody());

        $diff = array_diff($requiredKeys, $bodyKeys);
        if (!empty($diff)) {
            throw new PaymentException(
                __(
                    'Credorax required response data fields are missing: %1.',
                    implode(', ', $diff)
                )
            );
        }

        return $this;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return [];
    }

    public function getDataObject()
    {
        return new DataObject($this->getBody());
    }
}
