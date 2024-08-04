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
 * Shift4 Shift4 gateway void response model.
 */
class Cancel extends AbstractGateway implements ResponseInterface
{
    /**
     * @return Capture
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateTransaction()
    {
        return parent::updateTransaction();
    }
}
