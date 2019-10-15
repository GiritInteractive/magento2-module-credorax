<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

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
 */
class Factory
{
    /**
     * Set of requests.
     *
     * @var array
     */
    private $invokableClasses = [
        AbstractPaymentResponse::PAYMENT_AUTH_HANDLER => \Credorax\Credorax\Model\Response\Payment\Auth::class,
        AbstractPaymentResponse::PAYMENT_AUTH_TOKENIZATION_HANDLER => \Credorax\Credorax\Model\Response\Payment\AuthTokenization::class,
        AbstractPaymentResponse::PAYMENT_AUTH_USE_TOKEN_HANDLER => \Credorax\Credorax\Model\Response\Payment\AuthUseToken::class,
        AbstractPaymentResponse::PAYMENT_SALE_HANDLER => \Credorax\Credorax\Model\Response\Payment\Sale::class,
        AbstractPaymentResponse::PAYMENT_SALE_TOKENIZATION_HANDLER => \Credorax\Credorax\Model\Response\Payment\SaleTokenization::class,
        AbstractPaymentResponse::PAYMENT_SALE_USE_TOKEN_HANDLER => \Credorax\Credorax\Model\Response\Payment\SaleUseToken::class,
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
