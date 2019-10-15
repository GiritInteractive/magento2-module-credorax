<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Model;

/**
 * Credorax Credorax request interface.
 */
interface RequestInterface
{
    /**
     * Process current request type.
     *
     * @return RequestInterface
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function process();
}
