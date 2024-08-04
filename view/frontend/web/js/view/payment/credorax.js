/**
 * Shift4 Payments For Magento 2
 * https://www.shift4.com/
 *
 * @category Shift4
 * @package  Shift4_Shift4
 * @author   Girit-Interactive (https://www.girit-tech.com/)
 *
 *
 * Shift4 Shift4 js component.
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function(
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push({
            type: 'shift4',
            component: 'Shift4_Shift4/js/view/payment/method-renderer/shift4'
        });
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
