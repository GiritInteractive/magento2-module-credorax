<?php

namespace Credorax\Credorax\Model;

use Credorax\Credorax\Model\Request\AbstractGateway as AbstractGatewayRequest;
use Credorax\Credorax\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Credorax\Credorax\Model\Request\Factory as RequestFactory;
use Credorax\Credorax\Model\Response\Gateway\Dynamic3D as Dynamic3DResponse;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\GatewayException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Payment\Helper\Data;
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
     * @var \Credorax\Credorax\Model\Config
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
     * @param RequestFactory $requestFactory
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
        RequestFactory $requestFactory,
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

        /** @var RequestInterface $request */
        $request = $this->requestFactory->create(
            $payment->getAdditionalInformation(self::TRANSACTION_AUTH_CODE_KEY) ?
                AbstractGatewayRequest::GATEWAY_CAPTURE_METHOD :
                AbstractPaymentRequest::GATEWAY_SALE_METHOD,
            $payment,
            $amount
        );
        $response = $request->process();

        return $this;
    }

    private function processGateway(InfoInterface $payment, $amount)
    {
        /*$method = AbstractGatewayRequest::GATEWAY_AUTH_METHOD;
        if (0 && $this->credoraxConfig->is3dSecureEnabled()) {
            //$method = AbstractGatewayRequest::GATEWAY_DYNAMIC_3D_METHOD;
        } elseif ($this->credoraxConfig->getGatewayAction() === self::ACTION_AUTHORIZE_CAPTURE) {
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
            if ($this->credoraxConfig->getGatewayAction() === self::ACTION_AUTHORIZE_CAPTURE) {
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
