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

use Shift4\Shift4\Lib\Http\Client\Curl;
use Shift4\Shift4\Model\CardTokenization as CardTokenizationModel;
use Shift4\Shift4\Model\Config;
use Shift4\Shift4\Model\Shift4Method;
use Shift4\Shift4\Model\ResponseInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Shift4 AuthTokenization payment response model.
 */
class AuthTokenization extends Auth implements ResponseInterface
{
    /**
     * @var CardTokenizationModel
     */
    protected $_cardTokenizationModel;

    /**
     * @var string
     */
    protected $_token;

    /**
     * @method __construct
     * @param  Config                $shift4Config
     * @param  Curl                  $curl
     * @param  OrderPayment          $orderPayment
     * @param  CardTokenizationModel $cardTokenizationModel
     */
    public function __construct(
        Config $shift4Config,
        Curl $curl,
        OrderPayment $orderPayment,
        CardTokenizationModel $cardTokenizationModel
    ) {
        parent::__construct(
            $shift4Config,
            $curl,
            $orderPayment
        );
        $this->_cardTokenizationModel = $cardTokenizationModel;
    }

    /**
     * @return Dynamic3D
     */
    protected function processResponseData()
    {
        parent::processResponseData();

        $body = $this->getBody();
        $this->_token = isset($body['g1']) ? $body['g1'] : null;

        return $this;
    }

    /**
     * @return SaleTokenization
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    protected function updateTransaction()
    {
        parent::updateTransaction();

        if ($this->_token && $this->_shift4Config->isUsingVault() && $this->_orderPayment->getAdditionalInformation(Shift4Method::KEY_CC_SAVE)) {
            $this->_cardTokenizationModel
                ->setOrderPayment($this->_orderPayment)
                ->processCardPaymentToken($this->_token);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            [
                'g1',
            ]
        );
    }
}
