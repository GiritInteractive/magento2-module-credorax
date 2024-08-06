<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Block\Payment;

use Shift4\Shift4\Model\Shift4Method;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Serialize\Serializer\Base64Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Shift4 Shift4 payment device fingerprint block.
 *
 * @category Shift4
 * @package  Shift4_Shift4
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
            $this->checkoutSession->setData(Shift4Method::KEY_SHIFT4_3DS_METHOD, $this->parsedData['3ds_method']);
            $this->checkoutSession->setData(Shift4Method::KEY_SHIFT4_3DS_TRXID, $this->parsedData['3ds_trxid']);
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
    private function getShift4ThreeDSServerTransID()
    {
        return $this->getParsedData()['3ds_trxid'];
    }

    /**
     * @return string
     */
    public function getThreeDSMethodNotificationURL()
    {
        return $this->_urlBuilder->getUrl('shift4/payment_fingerprint/notification');
    }

    /**
     * @return string|null
     */
    public function getThreeDSMethodData()
    {
        return $this->serializer->serialize([
            "threeDSMethodNotificationURL" => $this->getThreeDSMethodNotificationURL(),
            "threeDSServerTransID" => $this->getShift4ThreeDSServerTransID(),
        ]);
    }

    /**
     * @return string|null
     */
    public function getThreeDSCompind()
    {
        $shift43dsCompind = (string) $this->getRequest()->getParam('threeDSMethodData');
        $this->checkoutSession->setData(Shift4Method::KEY_SHIFT4_3DS_COMPIND, $shift43dsCompind ?: null);
        return $shift43dsCompind;
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->getThreeDSMethodUrl() && $this->getShift4ThreeDSServerTransID()) {
            return parent::_toHtml();
        }

        return '';
    }
}
