<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */
namespace Credorax\Credorax\Controller\Payment\Fingerprint;

use Credorax\Credorax\Model\Config as CredoraxConfig;
use Credorax\Credorax\Model\CredoraxMethod;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Serialize\Serializer\Base64Json;

/**
 * Credorax Credorax payment device fingerprint notification controller.
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
     * @var CredoraxConfig
     */
    private $credoraxConfig;

    /**
     * @method __construct
     * @param  Context         $context
     * @param  CheckoutSession $checkoutSession
     * @param  CredoraxConfig  $credoraxConfig
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Base64Json $serializer,
        CredoraxConfig $credoraxConfig
    ) {
        parent::__construct($context);

        $this->checkoutSession = $checkoutSession;
        $this->serializer = $serializer;
        $this->credoraxConfig = $credoraxConfig;
    }

    public function execute()
    {
        $credorax3dCompind = (array) $this->serializer->unserialize($this->getRequest()->getParam('threeDSMethodData'));
        if ($credorax3dCompind['threeDSServerTransID'] === $this->checkoutSession->getData(CredoraxMethod::KEY_CREDORAX_3DS_TRXID)) {
            $this->checkoutSession->setData(CredoraxMethod::KEY_CREDORAX_3DS_COMPIND, 'Y');
        }

        $this->getResponse()->setBody("<script>parent.credorax_fingerprint_done=true;</script>");
    }
}
