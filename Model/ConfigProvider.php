<?php

namespace Credorax\Credorax\Model;

use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcGenericConfigProvider;

/**
 * Credorax config provider model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class ConfigProvider extends CcGenericConfigProvider
{
    /**
     * @var Config
     */
    private $credoraxConfig;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * ConfigProvider constructor.
     *
     * @param CcConfig                        $ccConfig
     * @param PaymentHelper                   $paymentHelper
     * @param Config                          $credoraxConfig
     * @param CheckoutSession                 $checkoutSession
     * @param array                           $methodCodes
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        Config $credoraxConfig,
        CheckoutSession $checkoutSession,
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
        $this->credoraxConfig = $credoraxConfig;
        $this->checkoutSession = $checkoutSession;
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
                    'hasNameOnCard' => true,
                    'cvvImageUrl' => $this->getCvvImageUrl(),
                    'merchantId' => $this->credoraxConfig->getMerchantId(),
                    'staticKey' => $this->credoraxConfig->getStaticKey(),
                    'reservedOrderId' => $this->getReservedOrderId(),
                    'keyCreationUrl' => $this->credoraxConfig->getCredoraxStoreUrl(),
                ],
            ],
        ];
        return $config;
    }

    private function getReservedOrderId()
    {
        $reservedOrderId = $this->checkoutSession->getQuote()->getReservedOrderId();
        if (!$reservedOrderId) {
            $this->checkoutSession->getQuote()->reserveOrderId()->save();
            $reservedOrderId = $this->checkoutSession->getQuote()->getReservedOrderId();
        }
        return $reservedOrderId;
    }
}
