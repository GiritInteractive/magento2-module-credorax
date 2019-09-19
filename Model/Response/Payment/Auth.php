<?php

namespace Credorax\Credorax\Model\Response\Payment;

use Credorax\Credorax\Model\CredoraxMethod;
use Credorax\Credorax\Model\Response\AbstractPayment;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax Auth payment response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Auth extends AbstractPayment implements ResponseInterface
{
    /**
     * @var string
     */
    protected $_authCode;

    /**
     * @return Dynamic3D
     */
    protected function processResponseData()
    {
        parent::processResponseData();

        $body = $this->getBody();
        $this->_authCode = isset($body['z4']) ? $body['z4'] : null;

        return $this;
    }

    /**
     * @return Auth
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    protected function updateTransaction()
    {
        parent::updateTransaction();

        if ($this->_authCode) {
            $this->_orderPayment->setAdditionalInformation(
                CredoraxMethod::KEY_CREDORAX_AUTH_CODE,
                $this->_authCode
            );
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getAuthCode()
    {
        return $this->_authCode;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            [
                'K',
                'O',
                'z1',
                'z4',
            ]
        );
    }
}
