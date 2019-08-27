<?php

namespace Credorax\Credorax\Block;

use Credorax\Credorax\Model\CredoraxMethod;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Gateway\ConfigInterface;

/**
 * Credorax Credorax configurable info block.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class ConfigurableInfo extends \Magento\Payment\Block\ConfigurableInfo
{
    /**
     * Object constructor.
     *
     * @param Context         $context
     * @param ConfigInterface $config
     * @param array           $data
     */
    public function __construct(
        Context $context,
        ConfigInterface $config,
        array $data = []
    ) {
        $data['methodCode'] = CredoraxMethod::METHOD_CODE;

        parent::__construct(
            $context,
            $config,
            $data
        );
    }

    /**
     * Returns label.
     *
     * @param string $field
     *
     * @return string|Phrase
     */
    protected function getLabel($field)
    {
        $labels = [
            CredoraxMethod::TRANSACTION_ID => __('Transaction Id'),
            CredoraxMethod::TRANSACTION_CARD_TYPE => __('Credit Card Type'),
        ];

        $label = $field;
        if (isset($labels[$field])) {
            $label = $labels[$field];
        }

        return $label;
    }

    /**
     * Returns value view
     *
     * @param string $field
     * @param string $value
     * @return string | Phrase
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function getValueView($field, $value)
    {
        return $value;
    }
}
