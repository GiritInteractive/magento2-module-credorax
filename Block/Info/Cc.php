<?php

namespace Credorax\Credorax\Block\Info;

use Credorax\Credorax\Model\CredoraxMethod;

class Cc extends \Magento\Payment\Block\Info\Cc
{

    /**
     * Prepare credit card related payment info
     *
     * @param \Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = [];
        if (($transactionId = $this->getInfo()->getAdditionalInformation(CredoraxMethod::TRANSACTION_ID))) {
            $data[(string)__('Transaction Id')] = $transactionId;
        }
        return $transport->setData(array_merge($data, $transport->getData()));
    }
}
