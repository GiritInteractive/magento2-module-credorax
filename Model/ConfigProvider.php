<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model;

use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Customer\Model\Session\Proxy as CustomerSession;
use Magento\Framework\UrlInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\CcConfig;
use Magento\Payment\Model\CcGenericConfigProvider;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Model\CreditCardTokenFactory;

/**
 * Shift4 config provider model.
 */
class ConfigProvider extends CcGenericConfigProvider
{
    /**
     * @var Config
     */
    private $shift4Config;

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
     * @param  Config                          $shift4Config
     * @param  CheckoutSession                 $checkoutSession
     * @param  CustomerSession                 $customerSession
     * @param  PaymentTokenManagementInterface $paymentTokenManagement
     * @param  array                           $methodCodes
     */
    public function __construct(
        CcConfig $ccConfig,
        PaymentHelper $paymentHelper,
        Config $shift4Config,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        PaymentTokenManagementInterface $paymentTokenManagement,
        array $methodCodes
    ) {
        $methodCodes = array_merge_recursive(
            $methodCodes,
            [Shift4Method::METHOD_CODE]
        );
        parent::__construct(
            $ccConfig,
            $paymentHelper,
            $methodCodes
        );
        $this->shift4Config = $shift4Config;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->urlBuilder = $this->shift4Config->getUrlBuilder();
    }
    /**
     * Return config array.
     *
     * @return array
     */
    public function getConfig()
    {
        if (!$this->shift4Config->isActive()) {
            return [];
        }

        $customerId = $this->customerSession->getCustomerId();
        $useVault = $customerId ? $this->shift4Config->isUsingVault() : false;
        $savedCards = $this->getSavedCards();
        $canSaveCard = $customerId ? true : false;

        $config = [
            'payment' => [
                Shift4Method::METHOD_CODE => [
                    'useVault' => $useVault,
                    'availableTypes' => $this->getCcAvailableTypes(Shift4Method::METHOD_CODE),
                    'months' => $this->getCcMonths(),
                    'years' => $this->getCcYears(),
                    'hasVerification' => $this->hasVerification(Shift4Method::METHOD_CODE),
                    'hasNameOnCard' => true,
                    'cvvImageUrl' => $this->getCvvImageUrl(),
                    'savedCards' => $savedCards,
                    'canSaveCard' => $canSaveCard,
                    'merchantId' => $this->shift4Config->getMerchantId(),
                    'staticKey' => $this->shift4Config->getStaticKey(),
                    'is3dSecureEnabled' => $this->shift4Config->is3dSecureEnabled(),
                    'reservedOrderId' => $this->getReservedOrderId(),
                    'keyCreationUrl' => $this->shift4Config->getShift4StoreUrl(),
                    'fingetprintIframeUrl' => $this->urlBuilder->getUrl('shift4/payment_fingerprint/form'),
                    'challengeRedirectUrl' => $this->urlBuilder->getUrl('shift4/payment_challenge/redirect'),
                ],
            ],
        ];
        $this->checkoutSession->unsData(Shift4Method::KEY_SHIFT4_3DS_COMPIND);
        $this->checkoutSession->unsShift4PaymentData();

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
        if (!$customerId || !$this->shift4Config->isUsingVault()) {
            return [];
        }

        $savedCards = [];
        $ccTypes = $this->getCcAvailableTypes(Shift4Method::METHOD_CODE);

        /** @var array $paymentTokens */
        $paymentTokens = $this->paymentTokenManagement->getListByCustomerId($customerId);

        foreach ($paymentTokens as $paymentToken) {
            if ($paymentToken->getType() !== CreditCardTokenFactory::TOKEN_TYPE_CREDIT_CARD) {
                continue;
            }
            if ($paymentToken->getPaymentMethodCode() !== Shift4Method::METHOD_CODE) {
                continue;
            }

            $cardDetails = json_decode($paymentToken->getDetails(), 1);

            $cardTypeName = isset($ccTypes[$cardDetails[Shift4Method::KEY_CC_TYPE]])
                ? $ccTypes[$cardDetails[Shift4Method::KEY_CC_TYPE]]
                : $cardDetails[Shift4Method::KEY_CC_TYPE];

            $cardLabel = sprintf(
                '%s xxxx-%s %s/%s',
                $cardTypeName,
                $cardDetails[Shift4Method::KEY_CC_LAST_4],
                str_pad($cardDetails[Shift4Method::KEY_CC_EXP_MONTH], 2, 0, STR_PAD_LEFT),
                substr($cardDetails[Shift4Method::KEY_CC_EXP_YEAR], -2)
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
