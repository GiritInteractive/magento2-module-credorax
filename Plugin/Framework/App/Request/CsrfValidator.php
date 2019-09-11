<?php

namespace Credorax\Credorax\Plugin\Framework\App\Request;

class CsrfValidator
{
    /**
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */
    public function aroundValidate(
        $subject,
        \Closure $proceed,
        $request,
        $action
    ) {
        if ($request->getFullActionName() === 'credorax_payment_fingerprint_notification') {
            return;
        }
        return $proceed($request, $action);
    }
}
