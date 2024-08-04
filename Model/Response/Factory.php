<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model\Response;

use Shift4\Shift4\Lib\Http\Client\Curl;
use Shift4\Shift4\Model\Response\AbstractGateway as AbstractGatewayResponse;
use Shift4\Shift4\Model\Response\AbstractPayment as AbstractPaymentResponse;
use Shift4\Shift4\Model\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

/**
 * Shift4 Shift4 response factory model.
 */
class Factory
{
    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        AbstractPaymentResponse::PAYMENT_AUTH_HANDLER => \Shift4\Shift4\Model\Response\Payment\Auth::class,
        AbstractPaymentResponse::PAYMENT_AUTH_TOKENIZATION_HANDLER => \Shift4\Shift4\Model\Response\Payment\AuthTokenization::class,
        AbstractPaymentResponse::PAYMENT_AUTH_USE_TOKEN_HANDLER => \Shift4\Shift4\Model\Response\Payment\AuthUseToken::class,
        AbstractPaymentResponse::PAYMENT_SALE_HANDLER => \Shift4\Shift4\Model\Response\Payment\Sale::class,
        AbstractPaymentResponse::PAYMENT_SALE_TOKENIZATION_HANDLER => \Shift4\Shift4\Model\Response\Payment\SaleTokenization::class,
        AbstractPaymentResponse::PAYMENT_SALE_USE_TOKEN_HANDLER => \Shift4\Shift4\Model\Response\Payment\SaleUseToken::class,
        AbstractGatewayResponse::GATEWAY_CAPTURE_HANDLER => \Shift4\Shift4\Model\Response\Gateway\Capture::class,
        AbstractGatewayResponse::GATEWAY_REFUND_HANDLER => \Shift4\Shift4\Model\Response\Gateway\Refund::class,
        AbstractGatewayResponse::GATEWAY_VOID_HANDLER => \Shift4\Shift4\Model\Response\Gateway\Cancel::class,
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
                    '%1 doesn\'t implement \Shift4\Shift4\Mode\ResponseInterface',
                    $className
                )
            );
        }

        return $model;
    }
}
