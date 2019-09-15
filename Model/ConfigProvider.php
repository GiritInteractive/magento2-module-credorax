<?php

namespace Credorax\Credorax\Model;

use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Customer\Model\Session\Proxy as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcGenericConfigProvider;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\CreditCardTokenFactory;

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
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @method __construct
     * @param  CcConfig                        $ccConfig
     * @param  PaymentHelper                   $paymentHelper
     * @param  Config                          $credoraxConfig
     * @param  CheckoutSession                 $checkoutSession
     * @param  CustomerSession                 $customerSession
     * @param  PaymentTokenManagementInterface $paymentTokenManagement
     * @param  array                           $methodCodes
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        Config $credoraxConfig,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        PaymentTokenManagementInterface $paymentTokenManagement,
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
        $this->customerSession = $customerSession;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->urlBuilder = $this->credoraxConfig->getUrlBuilder();
    }
    /**
     * Return config array.
     *
     * @return array
     */
    public function getConfig()
    {
        if (!$this->credoraxConfig->isActive()) {
            return [];
        }

        $customerId = $this->customerSession->getCustomerId();
        $useVault = $customerId ? $this->credoraxConfig->isUsingVault() : false;
        $savedCards = $this->getSavedCards();
        $canSaveCard = $customerId ? true : false;

        $config = [
            'payment' => [
                CredoraxMethod::METHOD_CODE => [
                    'useVault' => $useVault,
                    'availableTypes' => $this->getCcAvailableTypes(CredoraxMethod::METHOD_CODE),
                    'months' => $this->getCcMonths(),
                    'years' => $this->getCcYears(),
                    'hasVerification' => $this->hasVerification(CredoraxMethod::METHOD_CODE),
                    'hasNameOnCard' => true,
                    'cvvImageUrl' => $this->getCvvImageUrl(),
                    'savedCards' => $savedCards,
                    'canSaveCard' => $canSaveCard,
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

    /**
     * Return saved cards.
     *
     * @return array
     */
    private function getSavedCards()
    {
        $customerId = $this->customerSession->getCustomerId();
        if (!$customerId) {
            return [];
        }

        $savedCards = [];
        $ccTypes = $this->getCcAvailableTypes(CredoraxMethod::METHOD_CODE);

        /** @var array $paymentTokens */
        $paymentTokens = $this->paymentTokenManagement->getListByCustomerId($customerId);

        foreach ($paymentTokens as $paymentToken) {
            if ($paymentToken->getType() !== CreditCardTokenFactory::TOKEN_TYPE_CREDIT_CARD) {
                continue;
            }
            if ($paymentToken->getPaymentMethodCode() !== CredoraxMethod::METHOD_CODE) {
                continue;
            }

            $cardDetails = json_decode($paymentToken->getDetails(), 1);

            $cardTypeName = isset($ccTypes[$cardDetails[CredoraxMethod::KEY_CC_TYPE]])
                ? $ccTypes[$cardDetails[CredoraxMethod::KEY_CC_TYPE]]
                : $cardDetails[CredoraxMethod::KEY_CC_TYPE];

            $cardLabel = sprintf(
                '%s xxxx-%s %s/%s',
                $cardTypeName,
                $cardDetails[CredoraxMethod::KEY_CC_LAST_4],
                str_pad($cardDetails[CredoraxMethod::KEY_CC_EXP_MONTH], 2, 0, STR_PAD_LEFT),
                substr($cardDetails[CredoraxMethod::KEY_CC_EXP_YEAR], -2)
            );

            $savedCards[$paymentToken->getPublicHash()] = $cardLabel;
        }

        return $savedCards;
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
