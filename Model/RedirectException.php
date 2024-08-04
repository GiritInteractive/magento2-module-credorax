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

/**
 * RedirectException
 */
class RedirectException extends \Exception
{
    /**
     * @var string
     */
    protected $redirectUrl;

    /**
     * @param string $redirectUrl
     * @param \Exception $cause
     * @param int $code
     */
    public function __construct($redirectUrl, \Exception $cause = null, $code = 0)
    {
        $this->redirectUrl = $redirectUrl;
        parent::__construct($redirectUrl, (int)$code, $cause);
    }

    /**
     * Get Redirect URL
     *
     * @return string
     */
    public function getRedirectUrl()
    {
        return $this->redirectUrl;
    }
}
