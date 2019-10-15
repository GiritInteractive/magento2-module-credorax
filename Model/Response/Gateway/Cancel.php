<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Model\Response\Gateway;

use Credorax\Credorax\Model\Response\AbstractGateway;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax gateway void response model.
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
