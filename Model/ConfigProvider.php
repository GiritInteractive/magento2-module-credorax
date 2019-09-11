<?php

namespace Credorax\Credorax\Model;

use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\UrlInterface;
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
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @method __construct
     * @param  CcConfig        $ccConfig
     * @param  PaymentHelper   $paymentHelper
     * @param  Config          $credoraxConfig
     * @param  CheckoutSession $checkoutSession
     * @param  UrlInterface    $urlBuilder
     * @param  array           $methodCodes
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
        $this->urlBuilder = $this->credoraxConfig->getUrlBuilder();
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
                    'is3dSecureEnabled' => $this->credoraxConfig->is3dSecureEnabled(),
                    'reservedOrderId' => $this->getReservedOrderId(),
                    'keyCreationUrl' => $this->credoraxConfig->getCredoraxStoreUrl(),
                    'fingetprintIframeUrl' => $this->urlBuilder->getUrl('credorax/payment_fingerprint/form'),
                    'challengeRedirectUrl' => $this->urlBuilder->getUrl('credorax/payment_challenge/redirect'),
                ],
            ],
        ];
        $this->checkoutSession->unsData(CredoraxMethod::KEY_CREDORAX_3DS_COMPIND);
        $this->checkoutSession->unsCredoraxPaymentData();

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
