<?php

namespace Credorax\Credorax\Model\Response\Gateway;

use Credorax\Credorax\Model\Response\AbstractGateway;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax gateway refund response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Refund extends AbstractGateway implements ResponseInterface
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
