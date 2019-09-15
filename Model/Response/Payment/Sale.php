<?php

namespace Credorax\Credorax\Model\Response\Payment;

use Credorax\Credorax\Model\Response\AbstractPayment;
use Credorax\Credorax\Model\ResponseInterface;

/**
 * Credorax Credorax Sale payment response model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Sale extends AbstractPayment implements ResponseInterface
{
    /**
     * @return array
     */
    protected function getRequiredResponseDataKeys()
    {
        return array_merge_recursive(
            parent::getRequiredResponseDataKeys(),
            [
                'K',
                'O',
                'z1',
            ]
        );
    }
}
