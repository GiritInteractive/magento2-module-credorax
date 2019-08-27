<?php

namespace Credorax\Credorax\Model\Response\Payment;

use Credorax\Credorax\Model\CredoraxMethod;
use Credorax\Credorax\Model\Response\AbstractPayment;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax Sale payment response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Sale extends AbstractPayment implements ResponseInterface
{
    /**
     * @return Sale
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    protected function updateTransaction()
    {
        return parent::updateTransaction();
        /*parent::updateTransaction();

        $this->orderPayment->setAdditionalInformation(
            CredoraxMethod::TRANSACTION_ID,
            $this->getTransactionId()
        );

        $this->orderPayment->setAdditionalInformation(
            CredoraxMethod::TRANSACTION_RESPONSE_ID,
            $this->getResponseId()
        );

        $this->orderPayment->setAdditionalInformation(
            CredoraxMethod::KEY_CREDORAX_LAST_OPERATION_CODE,
            $this->getOperationCode()
        );

        return $this;*/
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            [
                'K',
                'O',
                'z1',
            ]
        );
    }
}
