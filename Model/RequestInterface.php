<?php

namespace Credorax\Credorax\Model;

/**
 * Credorax Credorax request interface.
 *
 * @category Credorax
 * @package  Credorax_Credorax
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
