<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Request;

use Shift4\Shift4\Model\Request\AbstractGateway as AbstractGatewayRequest;
use Shift4\Shift4\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Shift4\Shift4\Model\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Shift4 Shift4 request factory model.
 */
class Factory
{

    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        AbstractPaymentRequest::PAYMENT_AUTH_METHOD => \Shift4\Shift4\Model\Request\Payment\Auth::class,
        AbstractPaymentRequest::PAYMENT_AUTH_TOKENIZATION_METHOD => \Shift4\Shift4\Model\Request\Payment\AuthTokenization::class,
        AbstractPaymentRequest::PAYMENT_AUTH_USE_TOKEN_METHOD => \Shift4\Shift4\Model\Request\Payment\AuthUseToken::class,
        AbstractPaymentRequest::PAYMENT_SALE_METHOD => \Shift4\Shift4\Model\Request\Payment\Sale::class,
        AbstractPaymentRequest::PAYMENT_SALE_TOKENIZATION_METHOD => \Shift4\Shift4\Model\Request\Payment\SaleTokenization::class,
        AbstractPaymentRequest::PAYMENT_SALE_USE_TOKEN_METHOD => \Shift4\Shift4\Model\Request\Payment\SaleUseToken::class,
        AbstractGatewayRequest::GATEWAY_CAPTURE_METHOD => \Shift4\Shift4\Model\Request\Gateway\Capture::class,
        AbstractGatewayRequest::GATEWAY_REFUND_METHOD => \Shift4\Shift4\Model\Request\Gateway\Refund::class,
        AbstractGatewayRequest::GATEWAY_VOID_METHOD => \Shift4\Shift4\Model\Request\Gateway\Cancel::class,
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
    public function create($method, OrderPayment $orderPayment = null, $amount = 0.0)
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
                    '%1 doesn\'t implement \Shift4\Shift4\Mode\RequestInterface',
                    $className
                )
            );
        }

        return $model;
    }
}
