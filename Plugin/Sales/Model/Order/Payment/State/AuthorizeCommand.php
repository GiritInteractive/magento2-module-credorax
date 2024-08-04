<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Plugin\Sales\Model\Order\Payment\State;

use Shift4\Shift4\Model\Config;
use Shift4\Shift4\Model\Shift4Method;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;

/**
 * Plugin for product Details Block
 */
class AuthorizeCommand
{
    /**
     * @var Config
     */
    private $shift4Config;

    /**
     * @method __construct
     * @param  Config      $shift4Config
     */
    public function __construct(
        Config $shift4Config
    ) {
        $this->shift4Config = $shift4Config;
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
        if ($this->shift4Config->isActive() && $payment->getMethod() === Shift4Method::METHOD_CODE) {
            $order->setState(Order::STATE_NEW)->setStatus('pending');
        }
        return $result;
    }
}
