/**
 * Safecharge Safecharge js component.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'credorax',
                component: 'Credorax_Credorax/js/view/payment/method-renderer/credorax'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
