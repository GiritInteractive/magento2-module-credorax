<?php

namespace Credorax\Credorax\Model\Response\Gateway;

use Credorax\Credorax\Model\Response\AbstractGateway;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax gateway capture response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Capture extends AbstractGateway implements ResponseInterface
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
