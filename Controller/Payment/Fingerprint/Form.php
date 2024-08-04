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

use Magento\Framework\App\Action\Action;

/**
 * Shift4 Shift4 payment device fingerprint (iframe) form controller.
 */
class Form extends Action
{
    public function execute()
    {
        $this->getResponse()->setBody(
            $this->_view->getLayout()
                ->createBlock(\Shift4\Shift4\Block\Payment\Fingerprint::class)
                ->setTemplate('Shift4_Shift4::payment/fingerprint.phtml')
                ->toHtml()
        );
    }
}
