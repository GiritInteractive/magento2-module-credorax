<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Controller\Payment\Challenge;

use Shift4\Shift4\Model\CardTokenization as CardTokenizationModel;
use Shift4\Shift4\Model\Config as Shift4Config;
use Shift4\Shift4\Model\Shift4Method;
use Shift4\Shift4\Model\Payment;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Exception\PaymentException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\Transaction as OrderTransaction;
use Magento\Sales\Model\OrderFactory;

/**
 * Shift4 Shift4 challenge callback controller.
 */
class Callback extends Action
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var Shift4Config
     */
    private $shift4Config;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var CartManagementInterface
     */
    private $cartManagement;

    /**
     * @var CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Onepage
     */
    private $onepageCheckout;

    /**
     * @var CardTokenizationModel
     */
    private $cardTokenizationModel;

    /**
     * @method __construct
     * @param  Context                 $context
     * @param  OrderFactory            $orderFactory
     * @param  Shift4Config          $shift4Config
     * @param  DataObjectFactory       $dataObjectFactory
     * @param  CartManagementInterface $cartManagement
     * @param  CartRepositoryInterface $quoteRepository
     * @param  CheckoutSession         $checkoutSession
     * @param  Onepage                 $onepageCheckout
     * @param  CardTokenizationModel   $cardTokenizationModel
     */
    public function __construct(
        Context $context,
        OrderFactory $orderFactory,
        Shift4Config $shift4Config,
        DataObjectFactory $dataObjectFactory,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $quoteRepository,
        CheckoutSession $checkoutSession,
        Onepage $onepageCheckout,
        CardTokenizationModel $cardTokenizationModel
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->shift4Config = $shift4Config;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->cartManagement = $cartManagement;
        $this->quoteRepository = $quoteRepository;
        $this->checkoutSession = $checkoutSession;
        $this->onepageCheckout = $onepageCheckout;
        $this->cardTokenizationModel = $cardTokenizationModel;
    }
    /**
     * @return ResultInterface
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $params = $this->getRequest()->getParams();
            $paymentParams = $this->checkoutSession->getShift4PaymentData()->getData();

            $this->shift4Config->log('Challenge\Callback::execute() ', 'debug', [
                'params' => $params,
                'paymentParams' => $paymentParams
            ]);

            $resData = $this->dataObjectFactory->create()->setData(array_merge($paymentParams, $params));

            if (!(in_array($resData->getData('3ds_status'), ['Y','A']))) {
                if ($this->shift4Config->isDebugEnabled()) {
                    throw new PaymentException(__('Your payment failed. Details: %1', $this->shift4Config->get3dStatusMessage($resData->getData('3ds_status'))));
                } else {
                    throw new PaymentException(__('Your payment failed.'));
                }
            }
            if ((int)$resData->getData('z2')) {
                throw new PaymentException(__('Your payment failed. Details: %1', $resData->getData('z3') ?: __('Unknown')));
            }

            if (in_array($resData->getData('O'), [2,28]) && !$resData->getData('z4')) {
                if ($this->shift4Config->isDebugEnabled()) {
                    throw new PaymentException(__('Your payment failed. Details: Missing z4 param on challenge response.'));
                } else {
                    throw new PaymentException(__('Your payment failed.'));
                }
            }

            try {
                $this->onepageCheckout->getCheckoutMethod();
                $orderId = $this->cartManagement->placeOrder($this->getQuoteId());

                /** @var Order $order */
                $order = $this->orderFactory->create()->load($orderId);
                /** @var OrderPayment $payment */
                $orderPayment = $order->getPayment();

                $this->updateTransaction($orderPayment, $resData);

                $orderPayment->save();
                $order->save();
            } catch (\Exception $e) {
                $quote = $this->checkoutSession->getQuote();
                $quote->setIsActive(false);
                $this->quoteRepository->save($quote);
                throw new PaymentException(__('Your payment was successful, but an error occured on the server while placing the order. Please contact us. Details: %1', $e->getMessage()));
            }
        } catch (PaymentException $e) {
            $this->shift4Config->log('Challenge\Callback::execute() - Exception: ' . $e->getMessage(), 'error', [
                'params' => $params,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setUrl($this->_url->getUrl('checkout/cart'));
        }
        return $resultRedirect->setUrl($this->_url->getUrl('checkout/onepage/success/'));
    }

    /**
     * @return int
     * @throws PaymentException
     */
    private function getQuoteId()
    {
        $quoteId = (int)$this->getRequest()->getParam('quote');
        if ((int)$this->checkoutSession->getQuoteId() === $quoteId) {
            return $quoteId;
        }
        throw new PaymentException(
            __('Cart session has expired.')
        );
    }

    /**
     * @param OrderPayment $orderPayment
     * @param DataObject $resData
     * @return AbstractPayment
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function updateTransaction(OrderPayment $orderPayment, DataObject $resData)
    {
        $data = $resData->getData();
        ksort($data);

        $orderPayment->setTransactionAdditionalInfo(
            OrderTransaction::RAW_DETAILS,
            $data
        );

        $orderPayment->setAdditionalInformation(
            Shift4Method::KEY_SHIFT4_LAST_OPERATION_CODE,
            $resData->getData('O')
        );

        if ($transactionId = $resData->getData('z13')) {
            $orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_TRANSACTION_ID,
                $transactionId
            );
        }

        if ($responseId = $resData->getData('z1')) {
            $orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_RESPONSE_ID,
                $responseId
            );
        }

        if ($riskScore = $resData->getData('z21')) {
            $orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_RISK_SCORE,
                $riskScore
            );
        }

        if ($_3dsCavv = $resData->getData('3ds_cavv')) {
            $orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_3DS_CAVV,
                $_3dsCavv
            );
        }

        if ($_3dsEci = $resData->getData('3ds_eci')) {
            $orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_3DS_ECI,
                $_3dsEci
            );
        }

        if ($_3dsStatus = $resData->getData('3ds_status')) {
            $orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_3DS_STATUS,
                $_3dsStatus
            );
        }

        if ($_3dsTrxid = $resData->getData('3ds_trxid')) {
            $orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_3DS_TRXID,
                $_3dsTrxid
            );
        }

        if ($_3dsVersion = $resData->getData('3ds_version')) {
            $orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_3DS_VERSION,
                $_3dsVersion
            );
        }

        $orderPayment->getMethodInstance()->getInfoInstance()->addData(
            [
                Shift4Method::KEY_CC_LAST_4 => substr($resData->getData('b1'), -4),
                Shift4Method::KEY_CC_NUMBER => $resData->getData('b1'),
                Shift4Method::KEY_CC_EXP_MONTH => $resData->getData('b3'),
                Shift4Method::KEY_CC_EXP_YEAR => $resData->getData('b4'),
                Shift4Method::KEY_CC_OWNER => $resData->getData('c1'),
            ]
        );

        if (($authCode = $resData->getData('z4')) && in_array($resData->getData('O'), [2,28])) {
            $orderPayment->setAdditionalInformation(
                Shift4Method::KEY_SHIFT4_AUTH_CODE,
                $authCode
            );
        }

        if (
            ($token = $resData->getData('g1')) &&
            $this->shift4Config->isUsingVault() &&
            $orderPayment->getAdditionalInformation(Shift4Method::KEY_CC_SAVE)
        ) {
            $this->cardTokenizationModel
                ->setOrderPayment($orderPayment)
                ->processCardPaymentToken($token);
        }

        return $this;
    }
}
