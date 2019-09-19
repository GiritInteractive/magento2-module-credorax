<?php

namespace Credorax\Credorax\Controller\Payment\Challenge;

use Credorax\Credorax\Model\Config as CredoraxConfig;
use Credorax\Credorax\Model\QuoteManagement;
use Credorax\Credorax\Model\RedirectException as RedirectException;
use Magento\Checkout\Model\Session\Proxy as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Credorax Credorax challenge redirect controller.
 *
 * @category Credorax
 * @package  Credorax_Credorax
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
     * @var CredoraxConfig
     */
    private $credoraxConfig;

    /**
     * @method __construct
     * @param  Context         $context
     * @param  QuoteManagement $quoteManagement
     * @param  CheckoutSession $checkoutSession
     * @param  CredoraxConfig  $credoraxConfig
     */
    public function __construct(
        Context $context,
        QuoteManagement $quoteManagement,
        CheckoutSession $checkoutSession,
        CredoraxConfig $credoraxConfig
    ) {
        parent::__construct($context);
        $this->quoteManagement = $quoteManagement;
        $this->checkoutSession = $checkoutSession;
        $this->credoraxConfig = $credoraxConfig;
    }
    /**
     * @return ResultInterface
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        try {
            $quote = $this->checkoutSession->getQuote();
            $orderId = $this->quoteManagement->placeOrder($quote->getId());
        } catch (RedirectException $e) {
            $this->quoteManagement->rollbackAddressesAlias();
            return $resultRedirect->setUrl($e->getRedirectUrl());
        } catch (\Exception $e) {
            $this->quoteManagement->rollbackAddressesAlias();
            $this->credoraxConfig->log('Challenge\Redirect::execute() - Exception: ' . $e->getMessage(), 'error', [
                'trace' => $e->getTraceAsString(),
            ]);
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setUrl($this->_url->getUrl('checkout/cart'));
        }
        return $resultRedirect->setUrl($this->_url->getUrl('checkout/onepage/success/'));
    }
}
