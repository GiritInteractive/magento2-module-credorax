<?php

namespace Credorax\Credorax\Model;

use Magento\Framework\DataObject;
use Magento\Payment\Model\Method\Cc;


/**
 * Credorax payment model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class CredoraxMethod extends Cc
{
    /**
     * Method code const.
     */
    const METHOD_CODE = 'credorax';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

}
