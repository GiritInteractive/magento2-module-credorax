<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Model\Adminhtml\Source;

use Credorax\Credorax\Model\CredoraxMethod;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Credorax Credorax mode source model.
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
            CredoraxMethod::MODE_LIVE => __('Live'),
            CredoraxMethod::MODE_SANDBOX => __('Sandbox'),
        ];
    }
}
