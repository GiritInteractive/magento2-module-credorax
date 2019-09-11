<?php

namespace Credorax\Credorax\Model;

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
