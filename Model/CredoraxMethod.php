<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Model;

use Credorax\Credorax\Model\Config as CredoraxConfig;
use Credorax\Credorax\Model\RedirectException as RedirectException;
use Credorax\Credorax\Model\Request\AbstractGateway as AbstractGatewayRequest;
use Credorax\Credorax\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Credorax\Credorax\Model\Request\Factory as RequestFactory;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Customer\Model\Session\Proxy as CustomerSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
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
use Magento\Vault\Api\PaymentTokenManagementInterface;

/**
 * Credorax payment model.
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

    const KEY_CC_SAVE = 'cc_save';
    const KEY_CC_TYPE = 'cc_type';
    const KEY_CC_LAST_4 = 'cc_last_4';
    const KEY_CC_NUMBER = 'cc_number';
    const KEY_CC_EXP_YEAR = 'cc_exp_month';
    const KEY_CC_EXP_MONTH = 'cc_exp_year';
    const KEY_CC_OWNER = 'cc_owner';
    const KEY_CC_TOKEN = 'cc_token';
    const KEY_CC_TEMP_TOKEN = 'cc_temp_token';

    const KEY_CREDORAX_3DS_CAVV = 'credorax_3ds_cavv';
    const KEY_CREDORAX_3DS_COMPIND = 'credorax_3ds_compind';
    const KEY_CREDORAX_3DS_ECI = 'credorax_3ds_eci';
    const KEY_CREDORAX_3DS_METHOD = 'credorax_3ds_method';
    const KEY_CREDORAX_3DS_STATUS = 'credorax_3ds_status';
    const KEY_CREDORAX_3DS_TRXID = 'credorax_3ds_trxid';
    const KEY_CREDORAX_3DS_VERSION = 'credorax_3ds_version';
    const KEY_CREDORAX_AUTH_CODE = 'credorax_auth_code';
    const KEY_CREDORAX_LAST_OPERATION_CODE = 'credorax_last_operation_code';
    const KEY_CREDORAX_PKEY = 'credorax_pkey';
    const KEY_CREDORAX_PKEY_DATA = 'credorax_pkey_data';
    const KEY_CREDORAX_RESPONSE_ID = 'credorax_response_id';
    const KEY_CREDORAX_RISK_SCORE = 'credorax_risk_score';
    const KEY_CREDORAX_TRANSACTION_ID = 'credorax_transaction_id';

    /**
     * Gateway code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = \Credorax\Credorax\Block\Info\Cc::class;

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
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canUseInternal = false;

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
     * @var CustomerSession
     */
    private $customerSession;

    /**
     * @var CredoraxConfig
     */
    protected $credoraxConfig;

    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

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
     * @param  PaymentTokenManagementInterface $paymentTokenManagement
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
        CustomerSession $customerSession,
        CredoraxConfig $credoraxConfig,
        RequestFactory $requestFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
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
        $this->customerSession = $customerSession;
        $this->credoraxConfig = $credoraxConfig;
        $this->requestFactory = $requestFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
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

        $ccSave = (!empty($additionalData[self::KEY_CC_SAVE]) && $this->credoraxConfig->isUsingVault())
            ? (bool)$additionalData[self::KEY_CC_SAVE]
            : false;

        $ccType = !empty($additionalData[self::KEY_CC_TYPE])
            ? $additionalData[self::KEY_CC_TYPE]
            : null;

        $ccOwner = !empty($additionalData[self::KEY_CC_OWNER]) && strlen($additionalData[self::KEY_CC_OWNER]) >= 5
            ? $additionalData[self::KEY_CC_OWNER]
            : null;

        $ccToken = (!empty($additionalData[self::KEY_CC_TOKEN]) && $this->credoraxConfig->isUsingVault())
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
        $info->addData(
            [
                self::KEY_CC_TYPE => $ccType,
                self::KEY_CC_OWNER => $ccOwner,
            ]
        );

        //Token
        $tokenHash = $info->getAdditionalInformation(self::KEY_CC_TOKEN);
        if ($tokenHash === null) {
            return $this;
        }
        $token = $this->paymentTokenManagement->getByPublicHash(
            $tokenHash,
            $this->customerSession->getCustomerId()
        );
        if ($token->getId() === null) {
            $info->setAdditionalInformation(self::KEY_CC_TOKEN, null);
            return $this;
        }
        $tokenDetails = new DataObject((array) json_decode($token->getTokenDetails()));
        $info->addData(
            [
                self::KEY_CC_TYPE => $tokenDetails->getData(self::KEY_CC_TYPE),
                self::KEY_CC_LAST_4 => $tokenDetails->getData(self::KEY_CC_LAST_4),
                self::KEY_CC_NUMBER => $tokenDetails->getData(self::KEY_CC_NUMBER),
                self::KEY_CC_EXP_MONTH => $tokenDetails->getData(self::KEY_CC_EXP_MONTH),
                self::KEY_CC_EXP_YEAR => $tokenDetails->getData(self::KEY_CC_EXP_YEAR),
                self::KEY_CC_OWNER => $tokenDetails->getData(self::KEY_CC_OWNER),
            ]
        );

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
        if (!property_exists($credoraxPKeyData, 'PKey') || !$credoraxPKeyData->PKey || (property_exists($credoraxPKeyData, 'z2') && $credoraxPKeyData->z2)) {
            $errMessage = (property_exists($credoraxPKeyData, 'z3' && $credoraxPKeyData->z3)) ? $credoraxPKeyData->z3 : 'Credorax transaction failed, please make sure that the payment details are correct.';
            throw new LocalizedException(
                __($errMessage)
            );
        }
        $info->setAdditionalInformation(self::KEY_CREDORAX_PKEY, $credoraxPKeyData->PKey);

        $info->setAdditionalInformation(self::KEY_CREDORAX_3DS_METHOD, $this->checkoutSession->getData(self::KEY_CREDORAX_3DS_METHOD));
        $info->setAdditionalInformation(self::KEY_CREDORAX_3DS_TRXID, $this->checkoutSession->getData(self::KEY_CREDORAX_3DS_TRXID));
        $info->setAdditionalInformation(self::KEY_CREDORAX_3DS_COMPIND, $this->checkoutSession->getData(self::KEY_CREDORAX_3DS_COMPIND) ?: null);

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
     * @method getRequestMethod
     * @param  InfoInterface    $payment
     * @param  numeric           $amount
     * @return string
     */
    private function getRequestMethod(InfoInterface $payment, $amount)
    {
        if ($payment->getAdditionalInformation(self::KEY_CREDORAX_AUTH_CODE)) {
            $method = AbstractGatewayRequest::PAYMENT_CAPTURE_METHOD;
        } else {
            $token = $payment->getAdditionalInformation(self::KEY_CC_TOKEN);
            if ($token) {
                $method = AbstractPaymentRequest::PAYMENT_AUTH_USE_TOKEN_METHOD;
            } else {
                $method = $this->credoraxConfig->isUsingVault() ?
                    AbstractPaymentRequest::PAYMENT_AUTH_TOKENIZATION_METHOD :
                    AbstractPaymentRequest::PAYMENT_AUTH_METHOD;
            }
            if ($this->credoraxConfig->isAuthirizeAndCaptureAction()) {
                if ($token) {
                    $method = AbstractPaymentRequest::PAYMENT_SALE_USE_TOKEN_METHOD;
                } else {
                    $method = $this->credoraxConfig->isUsingVault() ?
                        AbstractPaymentRequest::PAYMENT_SALE_TOKENIZATION_METHOD :
                        AbstractPaymentRequest::PAYMENT_SALE_METHOD;
                }
            }
        }

        return $method;
    }

    /**
     * @method processPayment
     * @param  InfoInterface  $payment
     * @param  numeric         $amount
     * @return $this
     */
    private function processPayment(InfoInterface $payment, $amount)
    {
        if ($this->checkoutSession->getCredoraxPaymentData() !== null) {
            $this->checkoutSession->unsCredoraxPaymentData();
            return $this;
        }

        $method = $this->getRequestMethod($payment, $amount);

        /** @var RequestInterface $request */
        $request = $this->requestFactory->create(
            $method,
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

        $this->processPayment($payment, $amount);

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

        $this->processPayment($payment, $amount);

        return $this;
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
