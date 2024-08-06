<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Response\Gateway;

use Shift4\Shift4\Model\Response\AbstractGateway;
use Shift4\Shift4\Model\ResponseInterface;

/**
 * Shift4 Shift4 gateway capture response model.
 */
class Capture extends AbstractGateway implements ResponseInterface
{
    /**
     * @return Capture
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateTransaction()
    {
        parent::updateTransaction();

        if ($this->_responseId) {
            $this->_orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_RESPONSE_ID,
                $this->_responseId
            );
        }

        return $this;
    }
}
