<?php

namespace Credorax\Credorax\Controller\Payment\Fingerprint;

use Magento\Framework\App\Action\Action;

/**
 * Credorax Credorax payment device fingerprint (iframe) form controller.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Form extends Action
{
    public function execute()
    {
        $this->getResponse()->setBody(
            $this->_view->getLayout()
                ->createBlock(\Credorax\Credorax\Block\Payment\Fingerprint::class)
                ->setTemplate('Credorax_Credorax::payment/fingerprint.phtml')
                ->toHtml()
        );
    }
}
