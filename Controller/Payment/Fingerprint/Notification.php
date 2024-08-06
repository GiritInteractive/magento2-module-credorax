<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */
namespace Shift4\Shift4\Controller\Payment\Fingerprint;

use Shift4\Shift4\Model\Config as Shift4Config;
use Shift4\Shift4\Model\Shift4Method;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Serialize\Serializer\Base64Json;

/**
 * Shift4 Shift4 payment device fingerprint notification controller.
 */
class Notification extends Action
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Base64Json
     */
    private $serializer;

    /**
     * @var Shift4Config
     */
    private $shift4Config;

    /**
     * @method __construct
     * @param  Context         $context
     * @param  CheckoutSession $checkoutSession
     * @param  Shift4Config  $shift4Config
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Base64Json $serializer,
        Shift4Config $shift4Config
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->serializer = $serializer;
        $this->shift4Config = $shift4Config;
    }

    public function execute()
    {
        $shift43dCompind = (array) $this->serializer->unserialize($this->getRequest()->getParam('threeDSMethodData'));
        if ($shift43dCompind['threeDSServerTransID'] === $this->checkoutSession->getData(Shift4Method::KEY_SHIFT4_3DS_TRXID)) {
            $this->checkoutSession->setData(Shift4Method::KEY_SHIFT4_3DS_COMPIND, 'Y');
        }

        $this->getResponse()->setBody("<script>parent.shift4_fingerprint_done=true;</script>");
    }
}
