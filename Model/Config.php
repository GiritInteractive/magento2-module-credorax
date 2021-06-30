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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\UrlInterface;
use Magento\Payment\Model\MethodInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Credorax Credorax config model.
 */
class Config
{
    const MODULE_NAME = 'Credorax_Credorax';

    //= Integration (sandbox) URLs:
    const CREDORAX_GATEWAY_INTEGATION_URL = 'https://intconsole.credorax.com/intenv/service/gateway';
    const CREDORAX_STORE_INTEGATION_URL = 'https://ppskey-int.credorax.com/keypayment/rest/v2/store';
    const CREDORAX_PAYMENT_INTEGATION_URL = 'https://pps-int.credorax.com/keypayment/rest/v2/payment';
    //= Production (live) URLs:
    const CREDORAX_GATEWAY_PRODUCTION_URL = 'https://xts.gate.credorax.net/crax_gate/service/gateway';
    const CREDORAX_STORE_PRODUCTION_URL = 'https://ppskey.credorax.net/keypayment/rest/v2/store';
    const CREDORAX_PAYMENT_PRODUCTION_URL = 'https://PPS.credorax.net/keypayment/rest/v2/payment';

    /**
     * Scope config object.
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Store manager object.
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @method __construct
     * @param  ScopeConfigInterface  $scopeConfig
     * @param  StoreManagerInterface $storeManager
     * @param  EncryptorInterface    $encryptor
     * @param  LoggerInterface       $logger
     * @param  UrlInterface          $urlBuilder
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        EncryptorInterface $encryptor,
        LoggerInterface $logger,
        UrlInterface $urlBuilder
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->encryptor = $encryptor;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Return config path.
     *
     * @return string
     */
    private function getConfigPath()
    {
        return sprintf('payment/%s/', CredoraxMethod::METHOD_CODE);
    }

    /**
     * Return store manager.
     * @return StoreManagerInterface
     */
    public function getStoreManager()
    {
        return $this->storeManager;
    }

    /**
     * Return URL Builder
     * @return UrlInterface
     */
    public function getUrlBuilder()
    {
        return $this->urlBuilder;
    }

    /**
     * Return store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * Return config field value.
     *
     * @param string $fieldKey Field key.
     *
     * @return mixed
     */
    private function getConfigValue($fieldKey)
    {
        return $this->scopeConfig->getValue(
            $this->getConfigPath() . $fieldKey,
            ScopeInterface::SCOPE_STORE,
            $this->getCurrentStoreId()
        );
    }

    /**
     * Return bool value depends of that if payment method is active or not.
     *
     * @return bool
     */
    public function isActive()
    {
        return (bool)$this->getConfigValue('active');
    }

    /**
     * Return title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfigValue('title');
    }

    /**
     * Return merchant id.
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->getConfigValue(($this->isSandboxMode()) ? 'sandbox_merchant_id' : 'merchant_id');
    }

    /**
     * Return sub merchant id.
     *
     * @return string
     */
    public function getSubMerchantId()
    {
        return $this->getConfigValue(($this->isSandboxMode()) ? 'sandbox_sub_merchant_id' : 'sub_merchant_id');
    }

    /**
     * Return signature key.
     *
     * @return string
     */
    public function getSignatureKey()
    {
        return (($val = $this->getConfigValue(($this->isSandboxMode()) ? 'sandbox_signature_key' : 'signature_key'))) ? $this->encryptor->decrypt($val) : null;
    }

    /**
     * Return static key.
     *
     * @return string
     */
    public function getStaticKey()
    {
        return (($val = $this->getConfigValue(($this->isSandboxMode()) ? 'sandbox_static_key' : 'static_key'))) ? $this->encryptor->decrypt($val) : null;
    }

    /**
     * Return payment action configuration value (operation code).
     *
     * @return string
     */
    public function getPaymentAction()
    {
        return $this->getConfigValue('payment_action');
    }

    /**
     * @return bool
     */
    public function isAuthirizeAction()
    {
        return $this->getPaymentAction() === MethodInterface::ACTION_AUTHORIZE;
    }

    /**
     * @return bool
     */
    public function isAuthirizeAndCaptureAction()
    {
        return $this->getPaymentAction() === MethodInterface::ACTION_AUTHORIZE_CAPTURE;
    }

    /**
     * Return payment solution configuration value.
     *
     * @return string
     */
    public function getPaymentSolution()
    {
        return $this->getConfigValue('payment_solution');
    }

    /**
     * Return bool value depends of that if payment method sandbox mode
     * is enabled or not.
     *
     * @return bool
     */
    public function isSandboxMode()
    {
        return ($this->getConfigValue('mode') === CredoraxMethod::MODE_LIVE) ? false : true;
    }

    /**
     * Return bool value depends of that if payment method debug mode
     * is enabled or not.
     *
     * @return bool
     */
    public function isDebugEnabled()
    {
        return (bool)$this->getConfigValue('debug');
    }

    /**
     * Return cc types.
     *
     * @return string
     */
    public function getCcTypes()
    {
        return $this->getConfigValue('cctypes');
    }

    /**
     * @return bool
     */
    public function is3dSecureEnabled()
    {
        return (bool)$this->getConfigValue('enable_3d_secure');
    }

    /**
     * @return bool
     */
    public function isUsingSmart3d()
    {
        return (bool)$this->getConfigValue('use_smart_3d');
    }

    /**
     * Return use vault configuration value.
     *
     * @return bool
     */
    public function isUsingVault()
    {
        return (bool)$this->getConfigValue('use_vault');
    }

    /**
     * Return use ccv configuration value.
     *
     * @return bool
     */
    public function getUseCcv()
    {
        return (bool)$this->getConfigValue('useccv');
    }

    /**
     * Return billing descriptor configuration value.
     *
     * @return bool
     */
    public function getBillingDescriptor()
    {
        return $this->getConfigValue('billing_descriptor');
    }

    /**
     * @method getCredoraxGatewayUrl
     * @param string $path
     * @return string
     */
    public function getCredoraxGatewayUrl($path = "")
    {
        return ($this->isSandboxMode() ? self::CREDORAX_GATEWAY_INTEGATION_URL : self::CREDORAX_GATEWAY_PRODUCTION_URL) . (($path) ? '/' . $path : '');
    }

    /**
     * @method getCredoraxStoreUrl
     * @param string $path
     * @return string
     */
    public function getCredoraxStoreUrl($path = "")
    {
        return ($this->isSandboxMode() ? self::CREDORAX_STORE_INTEGATION_URL : self::CREDORAX_STORE_PRODUCTION_URL) . (($path) ? '/' . $path : '');
    }

    /**
     * @method getCredoraxPaymentUrl
     * @param string $path
     * @return string
     */
    public function getCredoraxPaymentUrl($path = "")
    {
        return ($this->isSandboxMode() ? self::CREDORAX_PAYMENT_INTEGATION_URL : self::CREDORAX_PAYMENT_PRODUCTION_URL) . (($path) ? '/' . $path : '');
    }

    /**
     * @method getCurrentStore
     */
    public function getCurrentStore()
    {
        return $this->storeManager->getStore();
    }

    /**
     * @method getCurrentStoreId
     * @return int
     */
    public function getCurrentStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @method log
     * @param  mixed   $message
     * @param  string  $type
     * @param  array   $data
     * @param  string  $prefix
     * @return $this
     */
    public function log($message, $type = "debug", $data = [], $prefix = '[Credorax] ')
    {
        if ($type !== 'debug' || $this->isDebugEnabled()) {
            if (!isset($data['store_id'])) {
                $data['store_id'] = $this->getCurrentStoreId();
            }
            switch ($type) {
                case 'error':
                    $this->logger->error($prefix . json_encode($message), $data);
                    break;
                case 'info':
                    $this->logger->info($prefix . json_encode($message), $data);
                    break;
                case 'debug':
                default:
                    $this->logger->debug($prefix . json_encode($message), $data);
                    break;
            }
        }
        return $this;
    }

    public function get3dStatusMessage($status)
    {
        switch ($status) {
            case 'A':
                return __('Attempts Processing Performed; Not Authenticated/Verified, but a proof of attempted authentication/verification is provided');
                break;
            case 'Y':
                return __('Authentication/ Account Verification Successful');
                break;
            case 'N':
                return __('Not Authenticated /Account Not Verified; Transaction denied');
                break;
            case 'R':
                return __('Authentication/ Account Verification Rejected; Issuer is rejecting authentication/verification and requests that authorisation not be attempted.');
                break;
            case 'U':
                return __('Authentication/ Account Verification Could Not Be Performed; Technical or other problem');
                break;
            default:
                return null;
                break;
        }
    }
}
