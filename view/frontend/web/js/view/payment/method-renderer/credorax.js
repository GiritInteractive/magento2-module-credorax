/**
 * Credorax Credorax js component.
 *
 * @category Credorax
 * @package  Credorax_Credorax
 */
define(
    [
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Paypal/js/action/set-payment-method',
        'Magento_Customer/js/customer-data',
        'jquery.redirect',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/action/create-billing-address'
    ],
    function(
        $,
        Component,
        additionalValidators,
        redirectOnSuccessAction,
        setPaymentMethodAction,
        customerData,
        jqueryRedirect,
        ko,
        quote,
        billingAddress
    ) {
        'use strict';

        var self = null;

        return Component.extend({
            defaults: {
                template: 'Credorax_Credorax/payment/credorax',
                isCcFormShown: true,
                creditCardToken: '',
                creditCardSave: 0,
                creditCardOwner: '',
                PKeyData: {},
                merchantId: '',
                staticKey: '',
                reservedOrderId: '',
                keyCreationUrl: ''
            },

            initObservable: function() {
                console.log(quote);

                self = this;

                self._super()
                    .observe([
                        'creditCardToken',
                        'creditCardSave',
                        'isCcFormShown',
                        'creditCardOwner',
                        'PKeyData'
                    ]);

                var savedCards = self.getCardTokens();
                if (savedCards.length > 0) {
                    self.creditCardToken(savedCards[0]['value']);
                }

                return self;
            },

            /**
             * @returns {String}
             */
            getCode: function() {
                return 'credorax';
            },

            /**
             * Check if payment is active
             *
             * @returns {Boolean}
             */
            isActive: function() {
                return true;
            },

            isShowLegend: function() {
                return true;
            },

            /** Returns is method available */
            isAvailable: function() {
                return true;
            },

            context: function() {
                return self;
            },

            getData: function() {
                return {
                    'method': self.item.method,
                    'additional_data': {
                        'cc_save': self.creditCardSave(),
                        'cc_type': self.creditCardType(),
                        'cc_owner': (self.creditCardOwner().length >= 5) ? self.creditCardOwner() : null,
                        'credorax_pkey_data': JSON.stringify(self.PKeyData())
                    }
                };
            },

            useVault: function() {
                var useVault = window.checkoutConfig.payment[self.getCode()].useVault;
                self.creditCardSave(useVault ? 1 : 0);

                return useVault;
            },

            canSaveCard: function() {
                return window.checkoutConfig.payment[self.getCode()].canSaveCard;
            },

            hasNameOnCard: function() {
                return window.checkoutConfig.payment[self.getCode()].hasNameOnCard;
            },

            getMerchantId: function() {
                return window.checkoutConfig.payment[self.getCode()].merchantId;
            },

            getStaticKey: function() {
                return window.checkoutConfig.payment[self.getCode()].staticKey;
            },

            getReservedOrderId: function() {
                return window.checkoutConfig.payment[self.getCode()].reservedOrderId;
            },

            getCardTokens: function() {
                var savedCards = window.checkoutConfig
                    .payment[self.getCode()]
                    .savedCards;

                return _.map(savedCards, function(value, key) {
                    return {
                        'value': key,
                        'label': value
                    };
                });
            },

            savedCardSelected: function(token) {
                if (token === undefined) {
                    self.isCcFormShown(true);
                } else {
                    self.isCcFormShown(false);
                }
            },

            is3dSecureEnabled: function() {
                return window.checkoutConfig.payment[self.getCode()].is3dSecureEnabled;
            },

            getKeyCreationUrl: function() {
                return window.checkoutConfig.payment[self.getCode()].keyCreationUrl;
            },

            getKeyCreationParams: function() {
                var params = {
                    "M": self.getMerchantId(),
                    "RequestID": self.getReservedOrderId(),
                    "Statickey": self.getStaticKey(),
                    "b1": self.creditCardNumber(),
                    "b3": self.creditCardExpMonth(),
                    "b4": self.creditCardExpYear(),
                    "b5": self.creditCardVerificationNumber()
                };
                if (self.creditCardOwner().length >= 5) {
                    params["c1"] = self.creditCardOwner();
                }
                console.log(params);
                return params;
            },

            placeOrder: function(data, event) {
                if (event) {
                    event.preventDefault();
                }

                if (self.validate() && additionalValidators.validate()) {
                    self.isPlaceOrderActionAllowed(false);

                    //= Key Creation (PKey)
                    $.ajax({
                        url: self.getKeyCreationUrl(),
                        data: self.getKeyCreationParams(),
                        method: 'post',
                        cache: false,
                        showLoader: true
                    }).always(function(res) {
                        console.log(res);
                        self.PKeyData(res);
                        self.getPlaceOrderDeferredObject()
                            .fail(
                                function() {
                                    self.isPlaceOrderActionAllowed(true);
                                }
                            ).done(
                                function() {
                                    self.afterPlaceOrder();
                                    if (self.redirectAfterPlaceOrder) {
                                        redirectOnSuccessAction.execute();
                                    }
                                }
                            );
                    });

                    return true;
                }

                return false;
            }

        });
    }
);