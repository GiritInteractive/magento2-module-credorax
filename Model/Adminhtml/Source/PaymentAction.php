<?php

namespace Credorax\Credorax\Model\Adminhtml\Source;

use Magento\Payment\Model\MethodInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Credorax Credorax mode source model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class PaymentAction implements OptionSourceInterface
{
    /**
     * Possible actions on order place.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => MethodInterface::ACTION_AUTHORIZE,
                'label' => __('Authorize'),
            ],
            [
                'value' => MethodInterface::ACTION_AUTHORIZE_CAPTURE,
                'label' => __('Authorize and Capture'),
            ]
        ];
    }
}
