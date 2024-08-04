<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

/**
 * Shift4 payment card tokenization model.
 */
class CardTokenization
{
    /**
     * @var PaymentTokenInterfaceFactory
     */
    private $paymentTokenFactory;

    /**
     * @var PaymentTokenManagementInterface
     */
    private $paymentTokenManagement;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var OrderPayment
     */
    private $orderPayment;

    /**
     * CardTokenization constructor.
     *
     * @param PaymentTokenInterfaceFactory    $paymentTokenFactory
     * @param PaymentTokenManagementInterface $paymentTokenManagement
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository
     */
    public function __construct(
        PaymentTokenInterfaceFactory $paymentTokenFactory,
        PaymentTokenManagementInterface $paymentTokenManagement,
        PaymentTokenRepositoryInterface $paymentTokenRepository
    ) {
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenManagement = $paymentTokenManagement;
        $this->paymentTokenRepository = $paymentTokenRepository;
    }

    /**
     * @param OrderPayment $orderPayment
     *
     * @return CardTokenization
     */
    public function setOrderPayment(OrderPayment $orderPayment)
    {
        $this->orderPayment = $orderPayment;

        return $this;
    }

    /**
     * @return PaymentTokenInterface
     * @throws LocalizedException
     */
    public function processCardPaymentToken($token)
    {
        if ($this->orderPayment === null) {
            throw new LocalizedException(
                __('Order payment object has been not set.')
            );
        }

        $customerId = $this->orderPayment->getOrder()->getCustomerId();

        $this->orderPayment->setAdditionalInformation(
            Shift4Method::KEY_CC_TOKEN,
            $token
        );

        $paymentToken = $this->paymentTokenManagement->getByGatewayToken($token, Shift4Method::METHOD_CODE, $customerId);
        if ($paymentToken && $paymentToken->getId()) {
            $paymentToken->setData(Shift4Method::KEY_CC_NUMBER, $this->orderPayment->getCcNumber());
            $paymentToken->setData(Shift4Method::KEY_CC_OWNER, $this->orderPayment->getCcOwner());
            $paymentToken->setGatewayToken($token);
            $this->paymentTokenRepository->save($paymentToken);
            return $paymentToken;
        }

        $paymentTokenDetails = [
            Shift4Method::KEY_CC_TYPE => $this->orderPayment->getCcType(),
            Shift4Method::KEY_CC_LAST_4 => $this->orderPayment->getCcLast4(),
            Shift4Method::KEY_CC_EXP_YEAR => $this->orderPayment->getCcExpYear(),
            Shift4Method::KEY_CC_EXP_MONTH => $this->orderPayment->getCcExpMonth(),
        ];

        $paymentTokenHash = hash(
            'sha256',
            implode('', $paymentTokenDetails) . $this->orderPayment->getOrder()->getCustomerId() . Shift4Method::METHOD_CODE
        );

        $paymentToken = $this->paymentTokenManagement->getByPublicHash($paymentTokenHash, $customerId);
        if ($paymentToken && $paymentToken->getId()) {
            $paymentToken->setData(Shift4Method::KEY_CC_NUMBER, $this->orderPayment->getCcNumber());
            $paymentToken->setData(Shift4Method::KEY_CC_OWNER, $this->orderPayment->getCcOwner());
            $paymentToken->setGatewayToken($token);
            $this->paymentTokenRepository->save($paymentToken);
            return $paymentToken;
        }

        $paymentTokenDetails[Shift4Method::KEY_CC_NUMBER] = $this->orderPayment->getCcNumber();
        $paymentTokenDetails[Shift4Method::KEY_CC_OWNER] = $this->orderPayment->getCcOwner();

        $paymentToken = $this->paymentTokenFactory->create()
            ->setCustomerId($customerId)
            ->setPublicHash($paymentTokenHash)
            ->setPaymentMethodCode(Shift4Method::METHOD_CODE)
            ->setGatewayToken($token)
            ->setTokenDetails(json_encode($paymentTokenDetails))
            ->setExpiresAt($this->getExpirationDate())
            ->setIsActive(1)
            ->setIsVisible(1);

        $this->paymentTokenRepository->save($paymentToken);

        return $paymentToken;
    }

    /**
     * @return string
     */
    private function getExpirationDate()
    {
        $expDate = \DateTime::createFromFormat(
            'y-m-d H:i:s',
            $this->orderPayment->getCcExpYear() . '-' . $this->orderPayment->getCcExpMonth() . '-01 00:00:00',
            new \DateTimeZone('UTC')
        );
        $expDate->add(new \DateInterval('P1M'));

        return $expDate->format('Y-m-d 00:00:00');
    }
}
