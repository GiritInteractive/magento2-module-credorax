<?php

namespace Credorax\Credorax\Model\Response;

use Credorax\Credorax\Lib\Http\Client\Curl;
use Credorax\Credorax\Model\Response\AbstractGateway as AbstractGatewayResponse;
use Credorax\Credorax\Model\Response\AbstractPayment as AbstractPaymentResponse;
use Credorax\Credorax\Model\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Credorax Credorax response factory model.
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
        AbstractPaymentResponse::PAYMENT_SALE_HANDLER => \Credorax\Credorax\Model\Response\Payment\Sale::class,
        AbstractPaymentResponse::PAYMENT_AUTH_HANDLER => \Credorax\Credorax\Model\Response\Payment\Auth::class,
        AbstractGatewayResponse::GATEWAY_CAPTURE_HANDLER => \Credorax\Credorax\Model\Response\Gateway\Capture::class,
        AbstractGatewayResponse::GATEWAY_REFUND_HANDLER => \Credorax\Credorax\Model\Response\Gateway\Refund::class,
        AbstractGatewayResponse::GATEWAY_VOID_HANDLER => \Credorax\Credorax\Model\Response\Gateway\Cancel::class,
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
     * Create response model.
     *
     * @param string            $type
     * @param Curl|null         $curl
     * @param OrderPayment|null $payment
     *
     * @return ResponseInterface
     * @throws LocalizedException
     */
    public function create(
        $type,
        $curl = null,
        $payment = null
    ) {
        $className = !empty($this->invokableClasses[$type])
            ? $this->invokableClasses[$type]
            : null;

        if ($className === null) {
            throw new LocalizedException(
                __('%1 type is not supported.')
            );
        }

        $model = $this->objectManager->create(
            $className,
            [
                'curl' => $curl,
                'orderPayment' => $payment,
            ]
        );
        if (!$model instanceof ResponseInterface) {
            throw new LocalizedException(
                __(
                    '%1 doesn\'t implement \Credorax\Credorax\Mode\ResponseInterface',
                    $className
                )
            );
        }

        return $model;
    }
}
