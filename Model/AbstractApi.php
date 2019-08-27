<?php

namespace Credorax\Credorax\Model;

/**
 * Credorax Credorax abstract api model.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
abstract class AbstractApi
{
    /**
     * @var Config
     */
    protected $_credoraxConfig;

    /**
     * Object initialization.
     *
     * @param Config           $config
     */
    public function __construct(
        Config $credoraxConfig
    ) {
        $this->_credoraxConfig = $credoraxConfig;
    }

    //= Credorax Helpers

    /**
     * @method getExponentsByCurrency
     * @param  string                 $currency
     * @return int
     */
    protected function getExponentsByCurrency($currency)
    {
        switch ($currency) {
            case 'CLP':
            case 'JPY':
            case 'KRW':
            case 'PYG':
            case 'VND':
                return 0;
                break;

            case 'TND':
            case 'BHD':
            case 'KWD':
            case 'JOD':
            case 'OMR':
                return 3;
                break;

            default:
                return 2;
                break;
        }
    }

    /**
     * @method getCcTypeNumberByCode
     * @param  string                $cctypeCode
     * @return int
     */
    protected function getCcTypeNumberByCode($cctypeCode)
    {
        switch ($cctypeCode) {
                case 'VI':
                    return 1;
                    break;

                case 'MC':
                    return 2;
                    break;

                case 'MI':
                    return 9;
                    break;

                default:
                    return 0;
                    break;
            }
    }

    /**
     * @method amountFormat
     * @param  float|int              $amount
     * @param  string                 $currency
     * @return int
     */
    protected function amountFormat($amount, $currency)
    {
        return number_format((float)$amount, $this->getExponentsByCurrency($currency), '', '');
    }
}
