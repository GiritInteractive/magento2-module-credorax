<?php

namespace Credorax\Credorax\Plugin\Sales\Model\Order\Payment\State;

use Credorax\Credorax\Model\Config;
use Credorax\Credorax\Model\CredoraxMethod;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;

/**
 * Plugin for product Details Block
 */
class AuthorizeCommand
{
    /**
     * @var Config
     */
    private $credoraxConfig;

    /**
     * @method __construct
     * @param  Config      $credoraxConfig
     */
    public function __construct(
        Config $credoraxConfig
    ) {
        $this->credoraxConfig = $credoraxConfig;
    }

    /**
     * @method beforeToHtml
     * @param \Magento\Catalog\Block\Product\View\Details $authorizeCommand
     * @param Phrase $result
     * @param OrderPaymentInterface $payment
     * @param string|float $amount
     * @param OrderInterface $order
     * @return Phrase
     */
    public function afterExecute(
        \Magento\Sales\Model\Order\Payment\State\AuthorizeCommand $authorizeCommand,
        $result,
        OrderPaymentInterface $payment,
        $amount,
        OrderInterface $order
    ) {
        if ($this->credoraxConfig->isActive() && $payment->getMethod() === CredoraxMethod::METHOD_CODE) {
            $order->setState(Order::STATE_NEW)->setStatus('pending');
            //$order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);

            //$operationCode = (int)$payment->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_LAST_OPERATION_CODE);
            //$transactionId = $payment->getAdditionalInformation(CredoraxMethod::KEY_CREDORAX_TRANSACTION_ID);

            /*if (in_array($operationCode, [2, 12, 28]) && $transactionId) {
                if ($payment->getLastTransId()) {
                    $payment->setParentTransactionId($payment->getLastTransId());
                }
                $payment->setTransactionId($transactionId);
                $payment->setIsTransactionClosed(0);
                $payment->addTransaction(Transaction::TYPE_AUTH);
            }*/
        }
        return $result;
    }
}
