/**
 * Safecharge Safecharge js component.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
define(
    [
        'Magento_Payment/js/view/payment/cc-form'
    ],
    function (
        Component
    ) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Credorax_Credorax/payment/credorax'
            },

            /**
             * @returns {String}
             */
            getCode: function () {
                return 'credorax';
            },

            /**
             * Check if payment is active
             *
             * @returns {Boolean}
             */
            isActive: function () {
                return true;
            },

            /** Returns is method available */
            isAvailable: function () {
                return true;
            }

        });
    }
);
