<?php
/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Shift4\Shift4\Controller\Payment\Challenge;

use Shift4\Shift4\Model\Config as Shift4Config;
use Shift4\Shift4\Model\RedirectException as RedirectException;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Quote\Model\QuoteManagement;

/**
 * Shift4 Shift4 challenge redirect controller.
 */
class Redirect extends Action
{
    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Onepage
     */
    private $onepageCheckout;

    /**
     * @var Shift4Config
     */
    private $shift4Config;

    /**
     * @method __construct
     * @param  Context         $context
     * @param  QuoteManagement $quoteManagement
     * @param  CheckoutSession $checkoutSession
     * @param  Onepage         $onepageCheckout
     * @param  Shift4Config  $shift4Config
     */
    public function __construct(
        Context $context,
        QuoteManagement $quoteManagement,
        CheckoutSession $checkoutSession,
        Onepage $onepageCheckout,
        Shift4Config $shift4Config
    ) {
        parent::__construct($context);
        $this->quoteManagement = $quoteManagement;
        $this->checkoutSession = $checkoutSession;
        $this->onepageCheckout = $onepageCheckout;
        $this->shift4Config = $shift4Config;
    }
    /**
     * @return ResultInterface
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        $this->shift4Config->log('Challenge\Redirect::execute() ', 'debug', [
            'params' => $this->getRequest()->getParams(),
        ]);

        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $quote = $this->checkoutSession->getQuote();
            $this->onepageCheckout->getCheckoutMethod();
            $orderId = $this->quoteManagement->placeOrder($quote->getId());
        } catch (\Exception $e) {
            if (is_a($e, RedirectException::class)) {
                return $resultRedirect->setUrl($e->getRedirectUrl());
            }
            $this->shift4Config->log('Challenge\Redirect::execute() - Exception: ' . $e->getMessage(), 'error', [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setUrl($this->_url->getUrl('checkout/cart'));
        }
        return $resultRedirect->setUrl($this->_url->getUrl('checkout/onepage/success/'));
    }
}
