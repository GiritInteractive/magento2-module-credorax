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

use Credorax\Credorax\Model\RedirectException as RedirectException;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote as QuoteEntity;

/**
 * Class QuoteManagement
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class QuoteManagement extends \Magento\Quote\Model\QuoteManagement implements \Magento\Quote\Api\CartManagementInterface
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $req;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    private $remoteAddr;

    /**
     * @inheritdoc
     */
    public function placeOrder($cartId, PaymentInterface $paymentMethod = null)
    {
        $this->remoteAddr = ObjectManager::getInstance()
            ->get(\Magento\Framework\HTTP\PhpEnvironment\RemoteAddress::class);
        $this->req = ObjectManager::getInstance()
            ->get(\Magento\Framework\App\RequestInterface::class);

        $quote = $this->quoteRepository->getActive($cartId);
        if ($paymentMethod) {
            $paymentMethod->setChecks([
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_CHECKOUT,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_COUNTRY,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_USE_FOR_CURRENCY,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_ORDER_TOTAL_MIN_MAX,
                    \Magento\Payment\Model\Method\AbstractMethod::CHECK_ZERO_TOTAL,
                ]);
            $quote->getPayment()->setQuote($quote);

            $data = $paymentMethod->getData();
            $quote->getPayment()->importData($data);
        } else {
            $quote->collectTotals();
        }

        if ($quote->getCheckoutMethod() === self::METHOD_GUEST) {
            $quote->setCustomerId(null);
            $quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
            if ($quote->getCustomerFirstname() === null && $quote->getCustomerLastname() === null) {
                $quote->setCustomerFirstname($quote->getBillingAddress()->getFirstname());
                $quote->setCustomerLastname($quote->getBillingAddress()->getLastname());
                if ($quote->getBillingAddress()->getMiddlename() === null) {
                    $quote->setCustomerMiddlename($quote->getBillingAddress()->getMiddlename());
                }
            }
            $quote->setCustomerIsGuest(true);
            $quote->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
        }

        $remoteAddress = $this->remoteAddr->getRemoteAddress();
        if ($remoteAddress !== false) {
            $quote->setRemoteIp($remoteAddress);
            $quote->setXForwardedFor(
                $this->req->getServer('HTTP_X_FORWARDED_FOR')
                );
        }

        $this->eventManager->dispatch('checkout_submit_before', ['quote' => $quote]);

        $order = $this->submit($quote);

        if (null == $order) {
            throw new LocalizedException(
                __('A server error stopped your order from being placed. Please try to place your order again.')
                );
        }

        $this->checkoutSession->setLastQuoteId($quote->getId());
        $this->checkoutSession->setLastSuccessQuoteId($quote->getId());
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());

        $this->eventManager->dispatch('checkout_submit_all_after', ['order' => $order, 'quote' => $quote]);
        return $order->getId();
    }

    /**
     * Submit quote
     *
     * @param Quote $quote
     * @param array $orderData
     * @return \Magento\Framework\Model\AbstractExtensibleModel|\Magento\Sales\Api\Data\OrderInterface|object
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function submitQuote(QuoteEntity $quote, $orderData = [])
    {
        $order = $this->orderFactory->create();
        $this->quoteValidator->validateBeforeSubmit($quote);
        if (!$quote->getCustomerIsGuest()) {
            if ($quote->getCustomerId()) {
                $this->_prepareCustomerQuote($quote);
                $this->customerManagement->validateAddresses($quote);
            }
            $this->customerManagement->populateCustomerInfo($quote);
        }
        $addresses = [];
        $quote->reserveOrderId();
        if ($quote->isVirtual()) {
            $this->dataObjectHelper->mergeDataObjects(
                \Magento\Sales\Api\Data\OrderInterface::class,
                $order,
                $this->quoteAddressToOrder->convert($quote->getBillingAddress(), $orderData)
            );
        } else {
            $this->dataObjectHelper->mergeDataObjects(
                \Magento\Sales\Api\Data\OrderInterface::class,
                $order,
                $this->quoteAddressToOrder->convert($quote->getShippingAddress(), $orderData)
            );
            $shippingAddress = $this->quoteAddressToOrderAddress->convert(
                $quote->getShippingAddress(),
                [
                    'address_type' => 'shipping',
                    'email' => $quote->getCustomerEmail()
                ]
            );
            $shippingAddress->setData('quote_address_id', $quote->getShippingAddress()->getId());
            $addresses[] = $shippingAddress;
            $order->setShippingAddress($shippingAddress);
            $order->setShippingMethod($quote->getShippingAddress()->getShippingMethod());
        }
        $billingAddress = $this->quoteAddressToOrderAddress->convert(
            $quote->getBillingAddress(),
            [
                'address_type' => 'billing',
                'email' => $quote->getCustomerEmail()
            ]
        );
        $billingAddress->setData('quote_address_id', $quote->getBillingAddress()->getId());
        $addresses[] = $billingAddress;
        $order->setBillingAddress($billingAddress);
        $order->setAddresses($addresses);
        $order->setPayment($this->quotePaymentToOrderPayment->convert($quote->getPayment()));
        $order->setItems($this->resolveItems($quote));
        if ($quote->getCustomer()) {
            $order->setCustomerId($quote->getCustomer()->getId());
        }
        $order->setQuoteId($quote->getId());
        $order->setCustomerEmail($quote->getCustomerEmail());
        $order->setCustomerFirstname($quote->getCustomerFirstname());
        $order->setCustomerMiddlename($quote->getCustomerMiddlename());
        $order->setCustomerLastname($quote->getCustomerLastname());

        $this->eventManager->dispatch(
            'sales_model_service_quote_submit_before',
            [
                'order' => $order,
                'quote' => $quote
            ]
        );
        try {
            $order = $this->orderManagement->place($order);
            $quote->setIsActive(false);
            $this->eventManager->dispatch(
                'sales_model_service_quote_submit_success',
                [
                    'order' => $order,
                    'quote' => $quote
                ]
            );
            $this->quoteRepository->save($quote);
        } catch (RedirectException $e) {
            $this->rollbackAddressesAlias();
            throw $e;
        } catch (\Exception $e) {
            $this->rollbackAddressesAlias($quote, $order, $e);
            throw $e;
        }
        return $order;
    }

    /**
     * Remove related to order and quote addresses and submit exception to further processing.
     *
     * @param Quote $quote
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Exception $e
     * @throws \Exception
     */
    public function rollbackAddressesAlias(
        QuoteEntity $quote = null,
        \Magento\Sales\Api\Data\OrderInterface $order = null,
        \Exception $e = null
    ) {
        try {
            if (!empty($this->addressesToSync)) {
                foreach ($this->addressesToSync as $addressId) {
                    $this->addressRepository->deleteById($addressId);
                }
            }
            if ($e === null) {
                return;
            }
            $this->eventManager->dispatch(
                'sales_model_service_quote_submit_failure',
                [
                    'order' => $order,
                    'quote' => $quote,
                    'exception' => $e,
                ]
            );
            // phpcs:ignore Magento2.Exceptions.ThrowCatch
        } catch (\Exception $consecutiveException) {
            $message = sprintf(
                "An exception occurred on 'sales_model_service_quote_submit_failure' event: %s",
                $consecutiveException->getMessage()
            );

            // phpcs:ignore Magento2.Exceptions.DirectThrow
            throw new \Exception($message, 0, $e);
        }
    }
}
