<?php

namespace Credorax\Credorax\Observer\Checkout;

use Credorax\Credorax\Model\Config;
use Credorax\Credorax\Model\CredoraxMethod;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\State\AuthorizeCommand;
use Magento\Sales\Model\Order\Payment\State\CaptureCommand;
use Magento\Sales\Model\Service\InvoiceService;

class SubmitAllAfter implements ObserverInterface
{
    /**
     * @var Config
     */
    private $credoraxConfig;

    /**
     * @var AuthorizeCommand
     */
    private $authorizeCommand;

    /**
     * @var CaptureCommand
     */
    private $captureCommand;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @method __construct
     * @param  Config             $credoraxConfig
     * @param  AuthorizeCommand   $authorizeCommand
     * @param  CaptureCommand     $captureCommand
     * @param  InvoiceService     $invoiceService
     * @param  TransactionFactory $transactionFactory
     */
    public function __construct(
        Config $credoraxConfig,
        AuthorizeCommand $authorizeCommand,
        CaptureCommand $captureCommand,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory
    ) {
        $this->credoraxConfig = $credoraxConfig;
        $this->authorizeCommand = $authorizeCommand;
        $this->captureCommand = $captureCommand;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
    }

    public function execute(Observer $observer)
    {
        try {

            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();

            /** @var OrderPayment $payment */
            $orderPayment = $order->getPayment();

            if (!$order->canInvoice() || $orderPayment->getMethod() !== CredoraxMethod::METHOD_CODE) {
                return $this;
            }

            $operationCode = (int)$orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_LAST_OPERATION_CODE);
            $transactionId = $orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_TRANSACTION_ID);

            if ($operationCode === 1 && $transactionId) {
                $message = $this->captureCommand->execute(
                    $orderPayment,
                    $order->getBaseGrandTotal(),
                    $order
                );

                if ($orderPayment->getLastTransId()) {
                    $orderPayment->setParentTransactionId($orderPayment->getLastTransId());
                }

                $orderPayment
                    ->setTransactionId($transactionId)
                    ->setIsTransactionPending(false)
                    ->setIsTransactionClosed(1);

                $invoice = $this->invoiceService->prepareInvoice($order);
                if (!$invoice) {
                    throw new \Magento\Framework\Exception\LocalizedException(__("We can't save the invoice right now."));
                }
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
                $invoice->register();
                //$invoice->getOrder()->setCustomerNoteNotify(false);
                $invoice->getOrder()->setIsInProcess(true);
                $order->addStatusHistoryComment($message, false);
                $transaction = $this->transactionFactory->create()
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();

                $orderPayment->save();
                $order->save();
            }
        } catch (\Exception $e) {
            $this->credoraxConfig->log('SubmitAllAfter::execute() - Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
            throw new LocalizedException(
                __('Your order have been placed, but there has been an error on the server, please contact us.')
            );
        }

        return $this;
    }
}
