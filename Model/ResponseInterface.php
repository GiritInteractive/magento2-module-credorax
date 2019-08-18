<?php

namespace Credorax\Credorax\Model;

/**
 * Credorax Credorax response interface.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
interface ResponseInterface
{
    /**
     * @return AbstractResponse
     * @throws \Magento\Framework\Exception\PaymentException
     */
    public function process();
}
