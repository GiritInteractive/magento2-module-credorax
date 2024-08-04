<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Response\Payment;

use Shift4\Shift4\Model\ResponseInterface;

/**
 * Shift4 AuthUseToken payment response model.
 */
class AuthUseToken extends Auth implements ResponseInterface
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
