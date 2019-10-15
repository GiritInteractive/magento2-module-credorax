<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Model\Request;

use Credorax\Credorax\Model\Request\AbstractGateway as AbstractGatewayRequest;
use Credorax\Credorax\Model\Request\AbstractPayment as AbstractPaymentRequest;
use Credorax\Credorax\Model\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Credorax Credorax request factory model.
 */
class Factory
{

    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        AbstractPaymentRequest::PAYMENT_AUTH_METHOD => \Credorax\Credorax\Model\Request\Payment\Auth::class,
        AbstractPaymentRequest::PAYMENT_AUTH_TOKENIZATION_METHOD => \Credorax\Credorax\Model\Request\Payment\AuthTokenization::class,
        AbstractPaymentRequest::PAYMENT_AUTH_USE_TOKEN_METHOD => \Credorax\Credorax\Model\Request\Payment\AuthUseToken::class,
        AbstractPaymentRequest::PAYMENT_SALE_METHOD => \Credorax\Credorax\Model\Request\Payment\Sale::class,
        AbstractPaymentRequest::PAYMENT_SALE_TOKENIZATION_METHOD => \Credorax\Credorax\Model\Request\Payment\SaleTokenization::class,
        AbstractPaymentRequest::PAYMENT_SALE_USE_TOKEN_METHOD => \Credorax\Credorax\Model\Request\Payment\SaleUseToken::class,
        AbstractGatewayRequest::GATEWAY_CAPTURE_METHOD => \Credorax\Credorax\Model\Request\Gateway\Capture::class,
        AbstractGatewayRequest::GATEWAY_REFUND_METHOD => \Credorax\Credorax\Model\Request\Gateway\Refund::class,
        AbstractGatewayRequest::GATEWAY_VOID_METHOD => \Credorax\Credorax\Model\Request\Gateway\Cancel::class,
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
                    '%1 doesn\'t implement \Credorax\Credorax\Mode\RequestInterface',
                    $className
                )
            );
        }

        return $model;
    }
}
