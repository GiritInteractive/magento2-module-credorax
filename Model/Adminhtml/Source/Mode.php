<?php

namespace Credorax\Credorax\Model\Adminhtml\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Credorax\Credorax\Model\CredoraxMethod;

/**
 * Credorax Credorax mode source model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Mode implements OptionSourceInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = [];
        foreach ($this->toArray() as $value => $label) {
            $optionArray[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'sandbox' => __('Sandbox'),
            'live' => __('Live'),
        ];
    }
}
