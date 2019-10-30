<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Observer\Checkout;

use Credorax\Credorax\Model\Config;
use Credorax\Credorax\Model\CredoraxMethod;
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

            if ($orderPayment->getMethod() !== CredoraxMethod::METHOD_CODE) {
                return $this;
            }

            $operationCode = (int)$orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_LAST_OPERATION_CODE);
            $transactionId = $orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_TRANSACTION_ID) ?: $orderPayment->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_3DS_TRXID);

            switch ($operationCode) {
                case 2:
                case 12:
                case 28:
                    $transactionType = Transaction::TYPE_AUTH;
                    break;

                case 1:
                case 11:
                case 23:
                    $transactionType = Transaction::TYPE_CAPTURE;
                    break;

                default:
                    $transactionType = false;
                    break;
            }

            if ($transactionId && $transactionType) {
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
            $this->credoraxConfig->log('SubmitAllAfter::execute() - Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), 'error');
            throw new LocalizedException(
                __('Your order have been placed, but there has been an error on the server, please contact us.')
            );
        }

        return $this;
    }
}
