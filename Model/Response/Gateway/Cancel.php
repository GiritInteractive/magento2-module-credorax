<?php

namespace Credorax\Credorax\Model\Response\Gateway;

use Credorax\Credorax\Model\Response\AbstractGateway;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax gateway void response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
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
