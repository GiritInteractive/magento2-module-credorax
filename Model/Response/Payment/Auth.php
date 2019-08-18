<?php

namespace Credorax\Credorax\Model\Response\Payment;

use Credorax\Credorax\Model\CredoraxMethod;
use Credorax\Credorax\Model\Response\AbstractPayment;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax Auth payment response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Auth extends AbstractPayment implements ResponseInterface
{
    /**
     * @return Auth
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    protected function updateTransaction()
    {
        parent::updateTransaction();

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

        return $this;
    }
}
