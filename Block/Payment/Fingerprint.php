<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Block\Payment;

use Credorax\Credorax\Model\CredoraxMethod;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Credorax Credorax payment device fingerprint block.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Fingerprint extends Template
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var JsonHelper
     */
    private $jsonHelper;

    /**
     * @var Base64Json
     */
    private $serializer;

    private $parsedData;

    /**
     * @method __construct
     * @param  Context         $context
     * @param  CheckoutSession $checkoutSession
     * @param  JsonHelper      $jsonHelper
     * @param  Base64Json      $serializer
     * @param  array           $data
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        JsonHelper $jsonHelper,
        Base64Json $serializer,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $data
        );

        $this->checkoutSession = $checkoutSession;
        $this->jsonHelper = $jsonHelper;
        $this->serializer = $serializer;
    }

    /**
     * Get block cache life time
     * @return null
     */
    protected function getCacheLifetime()
    {
        return null;
    }

    /**
     * @return int|null
     */
    private function getQuoteId()
    {
        return $this->checkoutSession->getQuote()->getId();
    }

    /**
     * @return array
     */
    private function getParsedData()
    {
        if (is_null($this->parsedData)) {
            $this->parsedData = array_merge(
                ['3ds_method' => null, '3ds_trxid' => null],
                (array)$this->jsonHelper->jsonDecode($this->getRequest()->getParam('3ds_data'))
            );
            $this->checkoutSession->setData(CredoraxMethod::KEY_CREDORAX_3DS_METHOD, $this->parsedData['3ds_method']);
            $this->checkoutSession->setData(CredoraxMethod::KEY_CREDORAX_3DS_TRXID, $this->parsedData['3ds_trxid']);
        }
        return $this->parsedData;
    }

    /**
     * @return string|null
     */
    public function getThreeDSMethodUrl()
    {
        return $this->getParsedData()['3ds_method'];
    }

    /**
     * @return string|null
     */
    private function getCredoraxThreeDSServerTransID()
    {
        return $this->getParsedData()['3ds_trxid'];
    }

    /**
     * @return string
     */
    public function getThreeDSMethodNotificationURL()
    {
        return $this->_urlBuilder->getUrl('credorax/payment_fingerprint/notification');
    }

    /**
     * @return string|null
     */
    public function getThreeDSMethodData()
    {
        return $this->serializer->serialize([
            "threeDSMethodNotificationURL" => $this->getThreeDSMethodNotificationURL(),
            "threeDSServerTransID" => $this->getCredoraxThreeDSServerTransID(),
        ]);
    }

    /**
     * @return string|null
     */
    public function getThreeDSCompind()
    {
        $credorax3dsCompind = (string) $this->getRequest()->getParam('threeDSMethodData');
        $this->checkoutSession->setData(CredoraxMethod::KEY_CREDORAX_3DS_COMPIND, $credorax3dsCompind ?: null);
        return $credorax3dsCompind;
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getThreeDSMethodUrl() && $this->getCredoraxThreeDSServerTransID()) {
            return parent::_toHtml();
        }

        return '';
    }
}
