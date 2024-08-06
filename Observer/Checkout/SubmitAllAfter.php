<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Observer\Checkout;

use Shift4\Shift4\Model\Config;
use Shift4\Shift4\Model\Shift4Method;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Sales\Model\Order\Payment\State\AuthorizeCommand;
use Magento\Sales\Model\Order\Payment\State\CaptureCommand;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Service\InvoiceService;

class SubmitAllAfter implements ObserverInterface
{
    /**
     * @var Config
     */
    private $shift4Config;

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
     * @param  Config             $shift4Config
     * @param  AuthorizeCommand   $authorizeCommand
     * @param  CaptureCommand     $captureCommand
     * @param  InvoiceService     $invoiceService
     * @param  TransactionFactory $transactionFactory
     */
    public function __construct(
        Config $shift4Config,
        AuthorizeCommand $authorizeCommand,
        CaptureCommand $captureCommand,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory
    ) {
        $this->shift4Config = $shift4Config;
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

            if ($orderPayment->getMethod() !== Shift4Method::METHOD_CODE) {
                return $this;
            }

            $operationCode = (int)$orderPayment->getAdditionalInformation(Shift4Method::KEY_SHIFT4_LAST_OPERATION_CODE);
            $transactionId = $orderPayment->getAdditionalInformation(Shift4Method::KEY_SHIFT4_TRANSACTION_ID) ?: $orderPayment->getAdditionalInformation(Shift4Method::KEY_SHIFT4_3DS_TRXID);

            if ($transactionId) {
                if (in_array($operationCode, [2,12,28])) {
                    $transactionType = Transaction::TYPE_AUTH;
                } elseif (in_array($operationCode, [1,11,23])) {
                    $transactionType = Transaction::TYPE_CAPTURE;
                } else {
                    $transactionType = $this->shift4Config->isAuthirizeAndCaptureAction() ? Transaction::TYPE_CAPTURE : Transaction::TYPE_AUTH;
                }

                if ($orderPayment->getLastTransId()) {
                    $orderPayment->setParentTransactionId($orderPayment->getLastTransId());
                }
                if ($transactionType === Transaction::TYPE_CAPTURE) {
                    $message = $this->captureCommand->execute(
                        $orderPayment,
                        $order->getBaseGrandTotal(),
                        $order
                    );
                    $orderPayment
                        ->setIsTransactionPending(false)
                        ->setIsTransactionClosed(1);
                } else {
                    $message = $this->authorizeCommand->execute(
                        $orderPayment,
                        $order->getBaseGrandTotal(),
                        $order
                    );
                    $orderPayment->setIsTransactionClosed(0);
                }

                $orderPayment->setTransactionId($transactionId);
                $orderPayment->addTransactionCommentsToOrder(
                    $orderPayment->addTransaction($transactionType),
                    $orderPayment->prependMessage($message)
                );

                $orderPayment->save();
                $order->save();
            }
        } catch (\Exception $e) {
            $this->shift4Config->log('SubmitAllAfter::execute() - Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
            throw new LocalizedException(
                __('Your order have been placed, but there has been an error on the server, please contact us.')
            );
        }

        return $this;
    }
}
