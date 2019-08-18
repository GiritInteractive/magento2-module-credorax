<?php

namespace Credorax\Credorax\Model\Request\Payment;

use Credorax\Credorax\Model\AbstractRequest;
use Credorax\Credorax\Model\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Credorax Credorax payment request factory model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
class Factory
{
    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        AbstractRequest::PAYMENT_SALE_METHOD => \Credorax\Credorax\Model\Request\Payment\Sale::class,
        AbstractRequest::PAYMENT_AUTH_METHOD => \Credorax\Credorax\Model\Request\Payment\Auth::class,
        AbstractRequest::PAYMENT_CAPTURE_METHOD => \Credorax\Credorax\Model\Request\Payment\Capture::class,
        AbstractRequest::PAYMENT_REFUND_METHOD => \Credorax\Credorax\Model\Request\Payment\Refund::class,
        AbstractRequest::PAYMENT_VOID_METHOD => \Credorax\Credorax\Model\Request\Payment\Cancel::class,
    ];

    /**
     * Object manager object.
     *
     * @var ObjectManagerInterface
     */
    private $objectManager;

    /**
     * Construct
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Create request model.
     *
     * @param string       $method
     * @param OrderPayment $orderPayment
     * @param float        $amount
     *
     * @return RequestInterface
     * @throws LocalizedException
     */
    public function create($method, $orderPayment, $amount = 0.0)
    {
        $className = !empty($this->invokableClasses[$method])
            ? $this->invokableClasses[$method]
            : null;

        if ($className === null) {
            throw new LocalizedException(
                __('%1 method is not supported.')
            );
        }

        $model = $this->objectManager->create(
            $className,
            [
                'orderPayment' => $orderPayment,
                'amount' => $amount,
            ]
        );
        if (!$model instanceof RequestInterface) {
            throw new LocalizedException(
                __(
                    '%1 doesn\'t implement \Credorax\Credorax\Mode\RequestInterface',
                    $className
                )
            );
        }

        return $model;
    }
}
