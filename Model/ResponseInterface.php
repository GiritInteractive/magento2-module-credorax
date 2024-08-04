<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model;

/**
 * Shift4 Shift4 response interface.
 */
interface ResponseInterface
{
    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function process();
}
