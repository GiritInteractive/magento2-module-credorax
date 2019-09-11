<?php

namespace Credorax\Credorax\Model;

use Credorax\Credorax\Model\Config as CredoraxConfig;
use Credorax\Credorax\Model\RedirectException as RedirectException;
use Credorax\Credorax\Model\Request\AbstractGateway as AbstractGatewayRequest;
use Credorax\Credorax\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Credorax\Credorax\Model\Request\Factory as RequestFactory;
use Credorax\Credorax\Model\Response\Gateway\Dynamic3D as Dynamic3DResponse;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\GatewayException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\ModuleListInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Payment\Helper\Data;
use Magento\Payment\Helper\Data as PaymentDataHelper;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Cc;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Vault\Api\Data\GatewayTokenFactoryInterface;

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
    const KEY_CREDORAX_3DS_METHOD = 'credorax_3ds_method';
    const KEY_CREDORAX_3DS_CAVV = 'credorax_3ds_cavv';
    const KEY_CREDORAX_3DS_ECI = 'credorax_3ds_eci';
    const KEY_CREDORAX_3DS_STATUS = 'credorax_3ds_status';
    const KEY_CREDORAX_3DS_TRXID = 'credorax_3ds_trxid';
    const KEY_CREDORAX_3DS_COMPIND = 'credorax_3ds_compind';
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
    const TRANSACTION_CARD_CVV = 'card_cvv';

    /**
     * Gateway code
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
    protected $_infoBlockType = \Credorax\Credorax\Block\Info\Cc::class;

    /**
     * Info block.
     *
     * @var string
     */
    //protected $_infoBlockType = \Credorax\Credorax\Block\ConfigurableInfo::class;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_isGateway = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canCapturePartial = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canRefund = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canVoid = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * Gateway Method feature.
     *
     * @var bool
     */
    protected $_isInitializeNeeded = false;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var CredoraxConfig
     */
    protected $credoraxConfig;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var GatewayTokenFactoryInterface
     */
    private $paymentTokenFactory;

    /**
     * @method __construct
     * @param  Context                         $context
     * @param  Registry                        $registry
     * @param  ExtensionAttributesFactory      $extensionFactory
     * @param  AttributeValueFactory           $customAttributeFactory
     * @param  PaymentDataHelper               $paymentData
     * @param  ScopeConfigInterface            $scopeConfig
     * @param  MagentoPaymentModelMethodLogger $logger
     * @param  ModuleListInterface             $moduleList
     * @param  TimezoneInterface               $localeDate
     * @param  CheckoutSession                 $checkoutSession
     * @param  CredoraxConfig                  $credoraxConfig
     * @param  RequestFactory                  $requestFactory
     * @param  AbstractResource|null           $resource
     * @param  AbstractDb|null                 $resourceCollection
     * @param array                            $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        PaymentDataHelper $paymentData,
        ScopeConfigInterface $scopeConfig,
        \Magento\Payment\Model\Method\Logger $logger,
        ModuleListInterface $moduleList,
        TimezoneInterface $localeDate,
        CheckoutSession $checkoutSession,
        CredoraxConfig $credoraxConfig,
        RequestFactory $requestFactory,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
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

        $this->checkoutSession = $checkoutSession;
        $this->credoraxConfig = $credoraxConfig;
        $this->requestFactory = $requestFactory;
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
     * @return Gateway
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
     * @return Gateway
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

        $info->setAdditionalInformation(self::KEY_CREDORAX_3DS_METHOD, $this->checkoutSession->getData(self::KEY_CREDORAX_3DS_METHOD));
        $info->setAdditionalInformation(self::KEY_CREDORAX_3DS_TRXID, $this->checkoutSession->getData(self::KEY_CREDORAX_3DS_TRXID));
        $info->setAdditionalInformation(self::KEY_CREDORAX_3DS_COMPIND, $this->checkoutSession->getData(self::KEY_CREDORAX_3DS_COMPIND) ?: null);

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
     * @return Gateway
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function authorize(InfoInterface $payment, $amount)
    {
        parent::authorize($payment, $amount);

        /** @var RequestInterface $request */
        $request = $this->requestFactory->create(
            AbstractPaymentRequest::PAYMENT_AUTH_METHOD,
            $payment,
            $amount
        );
        $response = $request->process();

        return $this;
    }

    /**
     * Capture payment method.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return Gateway
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function capture(InfoInterface $payment, $amount)
    {
        parent::capture($payment, $amount);

        if ($this->checkoutSession->getCredoraxPaymentData() !== null) {
            $this->checkoutSession->unsCredoraxPaymentData();
            return $this;
        }

        /** @var RequestInterface $request */
        $request = $this->requestFactory->create(
            $payment->getAdditionalInformation(self::TRANSACTION_AUTH_CODE_KEY) ?
                AbstractGatewayRequest::PAYMENT_CAPTURE_METHOD :
                AbstractPaymentRequest::PAYMENT_SALE_METHOD,
            $payment,
            $amount
        );
        $response = $request->process();

        if ($response->is3dsChallengeRequired()) {
            $this->checkoutSession->setCredoraxPaymentData($response->getDataObject());
            throw new RedirectException($response->get3dsAcsurl());
        }

        return $this;
    }

    private function processPayment(InfoInterface $payment, $amount)
    {
        /*$method = AbstractGatewayRequest::GATEWAY_AUTH_METHOD;
        if (0 && $this->credoraxConfig->is3dSecureEnabled()) {
            //$method = AbstractGatewayRequest::GATEWAY_DYNAMIC_3D_METHOD;
        } elseif ($this->credoraxConfig->getPaymentAction() === self::ACTION_AUTHORIZE_CAPTURE) {
            $method = AbstractGatewayRequest::GATEWAY_SALE_METHOD;
        }

        /** @var RequestInterface $request */
        /*$request = $this->requestFactory->create(
            $method,
            $payment,
            $amount
        );
        $response = $request->process();

        /*if ($method === AbstractGatewayRequest::GATEWAY_DYNAMIC_3D_METHOD) {
            $this->finalize3dSecureGateway($response, $payment, $amount);
        }*/

        return $this;
    }

    /**
     * @param Dynamic3DResponse $response
     * @param InfoInterface     $payment
     * @param float             $amount
     *
     * @return Gateway
     * @throws \RuntimeException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\GatewayException
     */
    private function finalize3dSecureGateway(
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
                $request = $this->requestFactory->create(
                    AbstractGatewayRequest::GATEWAY_CAPTURE_METHOD,
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
            $request = $this->requestFactory->create(
                AbstractGatewayRequest::GATEWAY_GATEWAY_3D_METHOD,
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
                self::TRANSACTION_USER_GATEWAY_OPTION_ID,
                $response->getUserGatewayOptionId()
                );
            $payment->setAdditionalInformation(
                self::TRANSACTION_CARD_CVV,
                $payment->getCcCid()
                );

            return $this;
        }

        throw new GatewayException(
            __('Unexpected response during 3d secure payment handling.')
            );
    }

    /**
     * Refund payment method.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return Gateway
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function refund(InfoInterface $payment, $amount)
    {
        parent::refund($payment, $amount);

        /** @var RequestInterface $request */
        $request = $this->requestFactory->create(
            AbstractGatewayRequest::GATEWAY_REFUND_METHOD,
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
     * @return Gateway
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
     * @return Gateway
     * @throws \Magento\Framework\Exception\LocalizedException
     *
     * @api
     */
    public function void(InfoInterface $payment)
    {
        parent::void($payment);

        /** @var RequestInterface $request */
        $request = $this->requestFactory->create(
            AbstractGatewayRequest::GATEWAY_VOID_METHOD,
            $payment
            );
        $request->process();

        return $this;
    }
}
