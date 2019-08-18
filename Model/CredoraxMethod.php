<?php

namespace Credorax\Credorax\Model;

use Credorax\Credorax\Model\Request\Payment\Factory as PaymentRequestFactory;
use Credorax\Credorax\Model\Response\Payment\Dynamic3D as Dynamic3DResponse;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\PaymentException;
use Magento\Framework\Model\Context;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Cc;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Vault\Api\Data\PaymentTokenFactoryInterface;

/**
 * Credorax payment model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class CredoraxMethod extends Cc
{
    /**
     * Method code const.
     */
    const METHOD_CODE = 'credorax';

    /**
     * Modes.
     */
    const MODE_SANDBOX = 'sandbox';
    const MODE_LIVE = 'live';

    /**
     * Method vault code const.
     */
    const CC_VAULT_CODE = 'credorax_vault';

    /**
     * Additional information const.
     */
    const KEY_CC_SAVE = 'cc_save';
    const KEY_CC_TYPE = 'cc_type';
    const KEY_CC_OWNER = 'cc_owner';
    const KEY_CREDORAX_PKEY = 'credorax_pkey';
    const KEY_CREDORAX_PKEY_DATA = 'credorax_pkey_data';
    const KEY_CC_TOKEN = 'cc_token';
    const KEY_CC_TEMP_TOKEN = 'cc_temp_token';
    const KEY_CREDORAX_LAST_OPERATION_CODE = 'credorax_last_operation_code';

    /**
     * Transaction keys const.
     */
    const TRANSACTION_RESPONSE_ID = 'transaction_response_id';
    const TRANSACTION_ORDER_ID = 'transaction_order_id';
    const TRANSACTION_AUTH_CODE_KEY = 'authorization_code';
    const TRANSACTION_ID = 'transaction_id';
    const TRANSACTION_CARD_NUMBER = 'card_number';
    const TRANSACTION_CARD_TYPE = 'card_type';
    const TRANSACTION_USER_PAYMENT_OPTION_ID = 'user_payment_option_id';
    const TRANSACTION_SESSION_TOKEN = 'session_token';
    const TRANSACTION_CARD_CVV = 'card_cvv';

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var string
     */
    //protected $_formBlockType = \Magento\Payment\Block\Form\Cc::class;

    /**
     * @var string
     */
    //protected $_infoBlockType = \Credorax\Credorax\Block\Info\Cc::class;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Payment Method feature.
     *
     * @var bool
     */
    protected $_isInitializeNeeded = false;

    /**
     * @var \Credorax\Credorax\Model\Config
     */
    protected $credoraxConfig;

    /**
     * @var PaymentRequestFactory
     */
    private $paymentRequestFactory;

    /**
     * @var PaymentTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Payment\Helper\Data $paymentData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Logger $logger
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Credorax\Credorax\Model\Config $credoraxConfig
     * @param PaymentRequestFactory $paymentRequestFactory
     * @param PaymentTokenFactoryInterface $paymentTokenFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Payment\Helper\Data $paymentData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Credorax\Credorax\Model\Config $credoraxConfig,
        PaymentRequestFactory $paymentRequestFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $moduleList,
            $localeDate,
            $resource,
            $resourceCollection,
            $data
        );
        $this->credoraxConfig = $credoraxConfig;
        $this->paymentRequestFactory = $paymentRequestFactory;
    }

    private function getPKeyData()
    {
        if (!$this->hasData(self::KEY_CREDORAX_PKEY_DATA)) {
            $this->setData(self::KEY_CREDORAX_PKEY_DATA, json_decode($this->getInfoInstance()->getAdditionalInformation(self::KEY_CREDORAX_PKEY_DATA)));
        }
        return $this->getData(self::KEY_CREDORAX_PKEY_DATA);
    }

    /**
     * Assign data.
     *
     * @param DataObject $data Data object.
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

        $credoraxPKeyData = !empty($additionalData[self::KEY_CREDORAX_PKEY_DATA])
            ? $additionalData[self::KEY_CREDORAX_PKEY_DATA]
            : null;

        $ccSave = !empty($additionalData[self::KEY_CC_SAVE])
            ? (bool)$additionalData[self::KEY_CC_SAVE]
            : false;

        $ccType = !empty($additionalData[self::KEY_CC_TYPE])
            ? $additionalData[self::KEY_CC_TYPE]
            : null;

        $ccOwner = !empty($additionalData[self::KEY_CC_OWNER]) && strlen($additionalData[self::KEY_CC_OWNER]) >= 5
            ? $additionalData[self::KEY_CC_OWNER]
            : null;

        $ccToken = !empty($additionalData[self::KEY_CC_TOKEN])
            ? $additionalData[self::KEY_CC_TOKEN]
            : null;

        if ($ccToken !== null) {
            $ccSave = false;
        }

        $info = $this->getInfoInstance();
        $info->setAdditionalInformation(self::KEY_CC_SAVE, $ccSave);
        $info->setAdditionalInformation(self::KEY_CC_TYPE, $ccType);
        $info->setAdditionalInformation(self::KEY_CC_OWNER, $ccOwner);
        $info->setAdditionalInformation(self::KEY_CC_TOKEN, $ccToken);
        $info->setAdditionalInformation(self::KEY_CREDORAX_PKEY_DATA, $credoraxPKeyData);

        return $this;
    }

    /**
     * Validate payment method information object.
     *
     * @return Payment
     * @throws LocalizedException
     */
    public function validate()
    {
        $info = $this->getInfoInstance();

        $credoraxPKeyData = $this->getPKeyData();
        if (!$credoraxPKeyData || !is_object($credoraxPKeyData)) {
            throw new LocalizedException(
                __('No response from Credorax gateway, please contact us or try again later.')
            );
        }
        if (!property_exists($credoraxPKeyData, 'PKey') || !$credoraxPKeyData->PKey) {
            throw new LocalizedException(
                __('Credorax transaction failed, please make sure that the payment details are correct.')
            );
        }
        $info->setAdditionalInformation(self::KEY_CREDORAX_PKEY, $credoraxPKeyData->PKey);

        $tokenHash = $info->getAdditionalInformation(self::KEY_CC_TOKEN);
        if ($tokenHash === null) {
            return $this;
        }

        if ($this->hasVerification()) {
            $customerId = $this->customerSession->getCustomerId();

            $token = $this->paymentTokenFactory
                ->getByPublicHash($tokenHash, $customerId);
            if ($token->getId() === null) {
                $info->setAdditionalInformation(self::KEY_CC_TOKEN, null);

                return $this;
            }

            $tokenDetails = json_decode($token->getTokenDetails(), 1);
            $cardType = $tokenDetails['cc_type'];

            $verificationRegEx = $this->getVerificationRegEx();

            $regExp = isset($verificationRegEx[$cardType])
                ? $verificationRegEx[$cardType]
                : '';

            if (!$regExp
                || !$info->getCcCid()
                || !preg_match($regExp, $info->getCcCid())
            ) {
                throw new LocalizedException(
                    __('Please enter a valid credit card verification number.')
                );
            }
        }

        return $this;
    }

    /**
     * Check if payment method can be used for provided currency.
     *
     * @param string $currencyCode
     *
     * @return bool
     */
    public function canUseForCurrency($currencyCode)
    {
        return true;
    }

    /**
     * Authorize payment method.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        parent::authorize($payment, $amount);

        $this->processPayment($payment, $amount);

        return $this;
    }

    /**
     * Capture payment method.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function capture(InfoInterface $payment, $amount)
    {
        parent::capture($payment, $amount);

        $this->processPayment($payment, $amount);

        return $this;
    }

    private function processPayment(InfoInterface $payment, $amount)
    {
        $method = AbstractRequest::PAYMENT_AUTH_METHOD;
        if (0 && $this->credoraxConfig->is3dSecureEnabled()) {
            //$method = AbstractRequest::PAYMENT_DYNAMIC_3D_METHOD;
        } elseif ($this->credoraxConfig->getPaymentAction() === self::ACTION_AUTHORIZE_CAPTURE) {
            $method = AbstractRequest::PAYMENT_SALE_METHOD;
        }

        /** @var RequestInterface $request */
        $request = $this->paymentRequestFactory->create(
            $method,
            $payment,
            $amount
        );
        $response = $request->process();

        /*if ($method === AbstractRequest::PAYMENT_DYNAMIC_3D_METHOD) {
            $this->finalize3dSecurePayment($response, $payment, $amount);
        }*/

        return $this;
    }

    /**
     * @param Dynamic3DResponse $response
     * @param InfoInterface     $payment
     * @param float             $amount
     *
     * @return Payment
     * @throws \RuntimeException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\PaymentException
     */
    private function finalize3dSecurePayment(
        Dynamic3DResponse $response,
        InfoInterface $payment,
        $amount
        ) {
        $threeDFlow = (int)$response->getThreeDFlow();
        $ascUrl = $response->getAscUrl();

        if ($threeDFlow === 0 && $ascUrl === null) {
            /**
             * If the merchant’s configured mode of operation is sale,
             * then no further action is required.
             * If the merchant’s configured mode of operation is auth-capture,
             * then the merchant should call captureTransaction method afterwards.
             */
            if ($this->credoraxConfig->getPaymentAction() === self::ACTION_AUTHORIZE_CAPTURE) {
                $request = $this->paymentRequestFactory->create(
                    AbstractRequest::PAYMENT_CAPTURE_METHOD,
                    $payment,
                    $amount
                    );
                $request->process();
            }

            return $this;
        }

        if ($threeDFlow === 1 && $ascUrl === null) {
            /**
             * The performed transaction will be 'sale’,
             * in order to complete the 'auth3D’ transaction
             * previously performed in dynamic3D method.
             */
            $request = $this->paymentRequestFactory->create(
                AbstractRequest::PAYMENT_PAYMENT_3D_METHOD,
                $payment,
                $amount
                );
            $request->process();

            return $this;
        }

        if ($threeDFlow === 1 && $ascUrl !== null) {
            /**
             * 1. Merchant should redirect to acsUrl.
             * 2. Merchant should call payment3D method afterwards.
             */
            $this->checkoutSession
                    ->setPaReq($response->getPaReq())
                    ->setAscUrl($ascUrl);

            $payment->setIsTransactionPending(true);

            $payment->setAdditionalInformation(
                self::TRANSACTION_USER_PAYMENT_OPTION_ID,
                $response->getUserPaymentOptionId()
                );
            $payment->setAdditionalInformation(
                self::TRANSACTION_CARD_CVV,
                $payment->getCcCid()
                );

            return $this;
        }

        throw new PaymentException(
            __('Unexpected response during 3d secure payment handling.')
            );
    }

    /**
     * Refund payment method.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function refund(InfoInterface $payment, $amount)
    {
        parent::refund($payment, $amount);

        /** @var RequestInterface $request */
        $request = $this->paymentRequestFactory->create(
            AbstractRequest::PAYMENT_REFUND_METHOD,
            $payment,
            $amount
            );
        $request->process();

        return $this;
    }

    /**
     * Cancel payment method.
     *
     * @param InfoInterface $payment
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function cancel(InfoInterface $payment)
    {
        parent::cancel($payment);

        $this->void($payment);

        return $this;
    }

    /**
     * Refund payment method.
     *
     * @param InfoInterface $payment
     *
     * @return Payment
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function void(InfoInterface $payment)
    {
        parent::void($payment);

        /** @var RequestInterface $request */
        $request = $this->paymentRequestFactory->create(
            AbstractRequest::PAYMENT_VOID_METHOD,
            $payment
            );
        $request->process();

        return $this;
    }
}
