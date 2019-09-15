<?php

namespace Credorax\Credorax\Model\Response\Payment;

use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax SaleUseToken payment response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class SaleUseToken extends Sale implements ResponseInterface
{
    /**
     * @return Dynamic3D
     */
    protected function processResponseData()
    {
        return parent::processResponseData();
    }

    /**
     * @return SaleTokenization
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    protected function updateTransaction()
    {
        return parent::updateTransaction();
    }

    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return parent::getRequiredResponseDataKeys();
    }
}
