<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Model\Response\Gateway;

use Credorax\Credorax\Model\Response\AbstractGateway;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax gateway capture response model.
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
                CredoraxMethod::KEY_CREDORAX_RESPONSE_ID,
                $this->_responseId
            );
        }

        return $this;
    }
}
