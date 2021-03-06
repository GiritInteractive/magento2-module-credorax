<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Model;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Payment as OrderPayment;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Api\Data\PaymentTokenInterfaceFactory;
use Magento\Vault\Api\PaymentTokenManagementInterface;
use Magento\Vault\Api\PaymentTokenRepositoryInterface;

/**
 * Credorax payment card tokenization model.
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
            CredoraxMethod::KEY_CC_TOKEN,
            $token
        );

        $paymentToken = $this->paymentTokenManagement->getByGatewayToken($token, CredoraxMethod::METHOD_CODE, $customerId);
        if ($paymentToken && $paymentToken->getId()) {
            $paymentToken->setData(CredoraxMethod::KEY_CC_NUMBER, $this->orderPayment->getCcNumber());
            $paymentToken->setData(CredoraxMethod::KEY_CC_OWNER, $this->orderPayment->getCcOwner());
            $paymentToken->setGatewayToken($token);
            $this->paymentTokenRepository->save($paymentToken);
            return $paymentToken;
        }

        $paymentTokenDetails = [
            CredoraxMethod::KEY_CC_TYPE => $this->orderPayment->getCcType(),
            CredoraxMethod::KEY_CC_LAST_4 => $this->orderPayment->getCcLast4(),
            CredoraxMethod::KEY_CC_EXP_YEAR => $this->orderPayment->getCcExpYear(),
            CredoraxMethod::KEY_CC_EXP_MONTH => $this->orderPayment->getCcExpMonth(),
        ];

        $paymentTokenHash = hash(
            'sha256',
            implode('', $paymentTokenDetails) . $this->orderPayment->getOrder()->getCustomerId() . CredoraxMethod::METHOD_CODE
        );

        $paymentToken = $this->paymentTokenManagement->getByPublicHash($paymentTokenHash, $customerId);
        if ($paymentToken && $paymentToken->getId()) {
            $paymentToken->setData(CredoraxMethod::KEY_CC_NUMBER, $this->orderPayment->getCcNumber());
            $paymentToken->setData(CredoraxMethod::KEY_CC_OWNER, $this->orderPayment->getCcOwner());
            $paymentToken->setGatewayToken($token);
            $this->paymentTokenRepository->save($paymentToken);
            return $paymentToken;
        }

        $paymentTokenDetails[CredoraxMethod::KEY_CC_NUMBER] = $this->orderPayment->getCcNumber();
        $paymentTokenDetails[CredoraxMethod::KEY_CC_OWNER] = $this->orderPayment->getCcOwner();

        $paymentToken = $this->paymentTokenFactory->create()
            ->setCustomerId($customerId)
            ->setPublicHash($paymentTokenHash)
            ->setPaymentMethodCode(CredoraxMethod::METHOD_CODE)
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
