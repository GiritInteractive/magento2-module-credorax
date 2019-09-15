<?php

namespace Credorax\Credorax\Controller\Payment\Challenge;

use Credorax\Credorax\Model\CardTokenization as CardTokenizationModel;
use Credorax\Credorax\Model\Config as CredoraxConfig;
use Credorax\Credorax\Model\CredoraxMethod;
use Credorax\Credorax\Model\Payment;
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
 * Credorax Credorax challenge callback controller.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Callback extends Action
{
    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var CredoraxConfig
     */
    private $credoraxConfig;

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
     * @param  CredoraxConfig          $credoraxConfig
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
        CredoraxConfig $credoraxConfig,
        DataObjectFactory $dataObjectFactory,
        CartManagementInterface $cartManagement,
        CartRepositoryInterface $quoteRepository,
        CheckoutSession $checkoutSession,
        Onepage $onepageCheckout,
        CardTokenizationModel $cardTokenizationModel
    ) {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->credoraxConfig = $credoraxConfig;
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
            $paymentParams = $this->checkoutSession->getCredoraxPaymentData()->getData();

            $this->credoraxConfig->log('Challenge\Callback::execute() ', 'debug', [
                'params' => $params,
                'paymentParams' => $paymentParams
            ]);

            $resData = $this->dataObjectFactory->create()->setData(array_merge($paymentParams, $params));

            if (!(in_array($resData->getData('3ds_status'), ['Y','A']))) {
                if ($this->credoraxConfig->isDebugEnabled()) {
                    throw new PaymentException(__('Your payment failed. Details: %1', $this->credoraxConfig->get3dStatusMessage($resData->getData('3ds_status'))));
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
            $this->credoraxConfig->log('Challenge\Callback::execute() - Exception: ' . $e->getMessage(), 'error', [
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
            CredoraxMethod::KEY_CREDORAX_LAST_OPERATION_CODE,
            $resData->getData('O')
        );

        if ($transactionId = $resData->getData('z13')) {
            $orderPayment->setAdditionalInformation(
                CredoraxMethod::TRANSACTION_ID,
                $transactionId
            );
        }

        if ($responseId = $resData->getData('z1')) {
            $orderPayment->setAdditionalInformation(
                CredoraxMethod::TRANSACTION_RESPONSE_ID,
                $responseId
            );
        }

        if ($_3dsCavv = $resData->getData('3ds_cavv')) {
            $orderPayment->setAdditionalInformation(
                CredoraxMethod::KEY_CREDORAX_3DS_CAVV,
                $_3dsCavv
            );
        }

        if ($_3dsEci = $resData->getData('3ds_eci')) {
            $orderPayment->setAdditionalInformation(
                CredoraxMethod::KEY_CREDORAX_3DS_ECI,
                $_3dsEci
            );
        }

        if ($_3dsStatus = $resData->getData('3ds_status')) {
            $orderPayment->setAdditionalInformation(
                CredoraxMethod::KEY_CREDORAX_3DS_STATUS,
                $_3dsStatus
            );
        }

        if ($_3dsTrxid = $resData->getData('3ds_trxid')) {
            $orderPayment->setAdditionalInformation(
                CredoraxMethod::KEY_CREDORAX_3DS_TRXID,
                $_3dsTrxid
            );
        }

        $orderPayment->getMethodInstance()->getInfoInstance()->addData(
            [
                'cc_last_4' => substr($resData->getData('b1'), -4),
                'cc_number' => $resData->getData('b1'),
                'cc_exp_month' => $resData->getData('b3'),
                'cc_exp_year' => $resData->getData('b4'),
                'cc_owner' => $resData->getData('c1'),
            ]
        );

        if ($authCode = $resData->getData('z4') && in_array($resData->getData('O'), [2,28])) {
            $orderPayment->setAdditionalInformation(
                CredoraxMethod::TRANSACTION_AUTH_CODE_KEY,
                $authCode
            );
        }

        if (
            ($token = $resData->getData('g1')) &&
            $this->credoraxConfig->isUsingVault() &&
            $orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CC_SAVE)
        ) {
            $this->cardTokenizationModel
                ->setOrderPayment($orderPayment)
                ->processCardPaymentToken($token);
        }

        return $this;
    }
}
