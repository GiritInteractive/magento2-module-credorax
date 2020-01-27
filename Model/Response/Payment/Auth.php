<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Model\Response\Payment;

use Credorax\Credorax\Model\CredoraxMethod;
use Credorax\Credorax\Model\Response\AbstractPayment;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax Auth payment response model.
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
