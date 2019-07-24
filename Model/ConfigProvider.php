<?php

namespace Credorax\Credorax\Model;


use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcGenericConfigProvider;
use Credorax\Credorax\Model\CredoraxMethod;

/**
 * Credorax config provider model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class ConfigProvider extends CcGenericConfigProvider
{
    /**
     * ConfigProvider constructor.
     *
     * @param CcConfig                        $ccConfig
     * @param PaymentHelper                   $paymentHelper
     * @param array                           $methodCodes
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        array $methodCodes
    ) {
        $methodCodes = array_merge_recursive(
            $methodCodes,
            [CredoraxMethod::METHOD_CODE]
        );
        parent::__construct(
            $ccConfig,
            $paymentHelper,
            $methodCodes
        );
    }
    /**
     * Return config array.
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [
            'payment' => [
                CredoraxMethod::METHOD_CODE => [
                    'availableTypes' => $this->getCcAvailableTypes(CredoraxMethod::METHOD_CODE),
                    'months' => $this->getCcMonths(),
                    'years' => $this->getCcYears(),
                    'hasVerification' => $this->hasVerification(CredoraxMethod::METHOD_CODE),
                    'cvvImageUrl' => $this->getCvvImageUrl()
                ],
            ],
        ];
        return $config;
    }
}
