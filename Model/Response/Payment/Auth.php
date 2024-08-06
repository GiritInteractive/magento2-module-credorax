<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Response\Payment;

use Shift4\Shift4\Model\Shift4Method;
use Shift4\Shift4\Model\Response\AbstractPayment;
use Shift4\Shift4\Model\ResponseInterface;

/**
 * Shift4 Shift4 Auth payment response model.
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
                Shift4Method::KEY_SHIFT4_AUTH_CODE,
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
        $params = array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            [
                'K',
                'O',
                'z1',
            ]
        );
        $body = $this->getBody();
        if (!(isset($body['3ds_acsurl']) && $body['3ds_acsurl'])) {
            $params[] = 'z4';
        }
        return $params;
    }
}
