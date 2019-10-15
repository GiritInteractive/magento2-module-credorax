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

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\CardTokenization as CardTokenizationModel;
use Credorax\Credorax\Model\Config;
use Credorax\Credorax\Model\CredoraxMethod;
use Credorax\Credorax\Model\ResponseInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Credorax SaleTokenization payment response model.
 */
class SaleTokenization extends Sale implements ResponseInterface
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
     * @param  Config                $credoraxConfig
     * @param  Curl                  $curl
     * @param  OrderPayment          $orderPayment
     * @param  CardTokenizationModel $cardTokenizationModel
     */
    public function __construct(
        Config $credoraxConfig,
        Curl $curl,
        OrderPayment $orderPayment,
        CardTokenizationModel $cardTokenizationModel
    ) {
        parent::__construct(
            $credoraxConfig,
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

        if ($this->_token && $this->_credoraxConfig->isUsingVault() && $this->_orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CC_SAVE)) {
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
