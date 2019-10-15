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

use Magento\Framework\App\Action\Action;

/**
 * Credorax Credorax payment device fingerprint (iframe) form controller.
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
