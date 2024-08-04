<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Adminhtml\Source;

use Magento\Payment\Model\Source\Cctype as PaymentCctype;

/**
 * Shift4 Shift4 Cctype source model.
 */
class Cctype extends PaymentCctype
{
    /**
     * Return all supported credit card types
     *
     * @return string[]
     */
    public function getAllowedTypes()
    {
        return ['VI', 'MC', 'MI', 'AE', 'DN'];
    }
}
