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
                is3dSecureEnabled: false,
                reservedOrderId: '',
                keyCreationUrl: '',
                fingetprintIframeUrl: '',
                challengeRedirectUrl: ''
            },

            initObservable: function() {
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
                        'cc_token': self.creditCardToken(),
                        'credorax_pkey_data': JSON.stringify(self.PKeyData()),
                        'credorax_3ds_compind': window.credorax_3ds_compind || null
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

            is3dSecureEnabled: function() {
                return window.checkoutConfig.payment[self.getCode()].is3dSecureEnabled;
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

            getIs3dSecureEnabled: function() {
                return window.checkoutConfig.payment[self.getCode()].is3dSecureEnabled;
            },

            getKeyCreationUrl: function() {
                return window.checkoutConfig.payment[self.getCode()].keyCreationUrl;
            },

            getFingetprintIframeUrl: function() {
                return window.checkoutConfig.payment[self.getCode()].fingetprintIframeUrl;
            },

            getChallengeRedirectUrl: function() {
                return window.checkoutConfig.payment[self.getCode()].challengeRedirectUrl;
            },

            getKeyCreationParams: function() {
                var params = {
                    "M": self.getMerchantId(),
                    "RequestID": self.getReservedOrderId(),
                    "Statickey": self.getStaticKey(),
                    "b5": self.creditCardVerificationNumber()
                };
                if (self.creditCardToken()) {
                    params["g1"] = self.creditCardToken();
                } else {
                    params["b1"] = self.creditCardNumber();
                    params["b3"] = self.creditCardExpMonth();
                    params["b4"] = self.creditCardExpYear();
                    if (self.creditCardOwner().length >= 5) {
                        params["c1"] = self.creditCardOwner();
                    }
                }

                return params;
            },

            placeOrderProceed: function() {
                self.isPlaceOrderActionAllowed(false);
                $('body').trigger('processStart');
                if (self.getIs3dSecureEnabled()) {
                    self.selectPaymentMethod();
                    setPaymentMethodAction(self.messageContainer)
                        .fail(
                            function() {
                                self.isPlaceOrderActionAllowed(true);
                                $('body').trigger('processStop');
                            }
                        ).done(
                            function() {
                                customerData.invalidate(['cart']);
                                $.mage.redirect(
                                    self.getChallengeRedirectUrl()
                                );
                            }
                        );
                } else {
                    self.getPlaceOrderDeferredObject()
                        .fail(
                            function() {
                                self.isPlaceOrderActionAllowed(true);
                                $('body').trigger('processStop');
                            }
                        ).done(
                            function() {
                                self.afterPlaceOrder();
                                if (self.redirectAfterPlaceOrder) {
                                    redirectOnSuccessAction.execute();
                                }
                            }
                        );
                }
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
                        cache: false
                    }).always(function(res) {
                        self.PKeyData(res);

                        if (self.getIs3dSecureEnabled() && res['3ds_method'] && res['3ds_trxid']) {
                            window.credorax_fingerprint_done = false;
                            window.credorax_fingerprint_form_submitted = false;

                            var credoraxFingerprintIframe = $('<iframe>', {
                                src: self.getFingetprintIframeUrl() + '?3ds_data=' + JSON.stringify({
                                    "3ds_method": res['3ds_method'],
                                    "3ds_trxid": res['3ds_trxid']
                                }),
                                id: 'credorax_fingerprint_iframe',
                                frameborder: 0,
                                scrolling: 'no',
                                css: {
                                    //"display": "none"
                                },
                            });
                            credoraxFingerprintIframe.appendTo('body');

                            window.credoraxFingerprintObs = setInterval(function() {
                                if (window.credorax_fingerprint_form_submitted) {
                                    clearInterval(window.credoraxFingerprintObs);
                                    window.credoraxFingerprintObs = setInterval(function() {
                                        if (window.credorax_fingerprint_done || Date.now() > window.credorax_fingerprint_form_submitted) {
                                            credoraxFingerprintIframe.remove();
                                            clearInterval(window.credoraxFingerprintObs);
                                            self.placeOrderProceed();
                                        }
                                    }, 100);
                                }
                            }, 100);
                        } else {
                            self.placeOrderProceed();
                        }
                    });
                } else {
                    return false;
                }
            }

        });
    }
);