<?php
/**
 * Credorax Payments For Magento 2
 * https://www.credorax.com/
 *
 * @category Credorax
 * @package  Credorax_Credorax
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 */

namespace Credorax\Credorax\Controller\Payment\Challenge;

use Credorax\Credorax\Model\Config as CredoraxConfig;
use Credorax\Credorax\Model\RedirectException as RedirectException;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Checkout\Model\Type\Onepage;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Quote\Model\QuoteManagement;

/**
 * Credorax Credorax challenge redirect controller.
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
     * @var CredoraxConfig
     */
    private $credoraxConfig;

    /**
     * @method __construct
     * @param  Context         $context
     * @param  QuoteManagement $quoteManagement
     * @param  CheckoutSession $checkoutSession
     * @param  Onepage         $onepageCheckout
     * @param  CredoraxConfig  $credoraxConfig
     */
    public function __construct(
        Context $context,
        QuoteManagement $quoteManagement,
        CheckoutSession $checkoutSession,
        Onepage $onepageCheckout,
        CredoraxConfig $credoraxConfig
    ) {
        parent::__construct($context);
        $this->quoteManagement = $quoteManagement;
        $this->checkoutSession = $checkoutSession;
        $this->onepageCheckout = $onepageCheckout;
        $this->credoraxConfig = $credoraxConfig;
    }
    /**
     * @return ResultInterface
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        $this->credoraxConfig->log('Challenge\Redirect::execute() ', 'debug', [
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
            $this->credoraxConfig->log('Challenge\Redirect::execute() - Exception: ' . $e->getMessage(), 'error', [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setUrl($this->_url->getUrl('checkout/cart'));
        }
        return $resultRedirect->setUrl($this->_url->getUrl('checkout/onepage/success/'));
    }
}
