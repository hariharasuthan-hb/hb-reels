/**
 * Stripe Payment Element Integration
 * Handles subscription payment form initialization and submission
 */

(function() {
    'use strict';

    // Configuration from Blade template
    const config = window.stripeCheckoutConfig || {};
    
    if (!config.publishableKey || !config.clientSecret) {
        return;
    }

    // Initialize Stripe
    const stripe = Stripe(config.publishableKey);
    
    // Create Elements instance with client secret
    const elements = stripe.elements({
        clientSecret: config.clientSecret,
        appearance: {
            variables: {
                colorPrimary: '#3B82F6',
            },
        },
    });
    
    const paymentElement = elements.create('payment', {
        layout: 'tabs',
        fields: {
            billingDetails: 'auto',
        },
        // Disable wallets to show only card entry
        wallets: {
            applePay: 'never',
            googlePay: 'never',
        },
    });
    
    function mountPaymentElement() {
        const element = document.getElementById('stripe-payment-element');
        if (element) {
            try {
                paymentElement.mount('#stripe-payment-element');
            } catch (error) {
                const errorDiv = document.getElementById('stripe-errors');
                if (errorDiv) {
                    errorDiv.textContent = 'Error loading payment form: ' + error.message;
                    errorDiv.classList.remove('hidden');
                }
            }
        } else {
            setTimeout(mountPaymentElement, 100);
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', mountPaymentElement);
    } else {
        mountPaymentElement();
    }
    
    // Wait for DOM to be ready
    function initializeForm() {
        const form = document.getElementById('subscription-form');
        const submitButton = document.getElementById('stripe-submit-btn');
        
        if (!form || !submitButton) {
            setTimeout(initializeForm, 100);
            return;
        }
        // Track if form is already being submitted to prevent double submission
        let isSubmitting = false;
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Prevent double submission
            if (isSubmitting) {
                return;
            }
            
            isSubmitting = true;
            submitButton.disabled = true;
            const originalText = submitButton.textContent;
            submitButton.textContent = 'Processing...';
            
            try {
                // Call elements.submit() first to validate the form
                const {error: submitError} = await elements.submit();
                
                if (submitError) {
                    const errorDiv = document.getElementById('stripe-errors');
                    if (errorDiv) {
                        errorDiv.textContent = submitError.message;
                        errorDiv.classList.remove('hidden');
                    }
                    isSubmitting = false;
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                    return;
                }
                
                // Check payment intent status before confirming
                const isSetupIntent = config.clientSecret.startsWith('seti_');
                let intentId = null;
                
                // Extract intent ID from client secret
                if (isSetupIntent) {
                    intentId = config.clientSecret.split('_secret_')[0];
                } else {
                    intentId = config.clientSecret.split('_secret_')[0];
                }
                
                // Retrieve intent to check current status
                let currentIntent = null;
                try {
                    if (isSetupIntent) {
                        currentIntent = await stripe.retrieveSetupIntent(intentId);
                    } else {
                        currentIntent = await stripe.retrievePaymentIntent(intentId);
                    }
                    
                    // If already succeeded, redirect to success page
                    if (currentIntent && (currentIntent.setupIntent || currentIntent.paymentIntent)) {
                        const intent = currentIntent.setupIntent || currentIntent.paymentIntent;
                        if (intent.status === 'succeeded' || intent.status === 'processing') {
                            let successUrl = config.successUrl || window.location.origin + '/member/subscription/success';
                            const separator = successUrl.includes('?') ? '&' : '?';
                            if (currentIntent.paymentIntent) {
                                successUrl += separator + 'payment_intent=' + intent.id;
                            } else if (currentIntent.setupIntent) {
                                successUrl += separator + 'setup_intent=' + intent.id;
                            }
                            window.location.href = successUrl;
                            return;
                        }
                    }
                } catch (retrieveError) {
                    // If retrieve fails, continue with confirmation attempt
                    console.warn('Could not retrieve intent status:', retrieveError);
                }
                
                // Now proceed with confirmation
                const result = isSetupIntent
                    ? await stripe.confirmSetup({
                        elements,
                        clientSecret: config.clientSecret,
                        confirmParams: { return_url: config.successUrl || window.location.origin + '/member/subscription/success' },
                        redirect: 'if_required',
                    })
                    : await stripe.confirmPayment({
                        elements,
                        clientSecret: config.clientSecret,
                        confirmParams: { return_url: config.successUrl || window.location.origin + '/member/subscription/success' },
                        redirect: 'if_required',
                    });
                
                const {error, paymentIntent, setupIntent} = result;
                
                if (error) {
                    // Handle specific error: payment_intent_unexpected_state
                    if (error.code === 'payment_intent_unexpected_state' || 
                        error.code === 'setup_intent_unexpected_state' ||
                        error.message?.includes('already succeeded') ||
                        error.message?.includes('already confirmed')) {
                        // Payment already succeeded, redirect to success
                        let successUrl = config.successUrl || window.location.origin + '/member/subscription/success';
                        const separator = successUrl.includes('?') ? '&' : '?';
                        
                        // Try to get intent ID from error object
                        if (error.payment_intent?.id) {
                            successUrl += separator + 'payment_intent=' + error.payment_intent.id;
                        } else if (error.setup_intent?.id) {
                            successUrl += separator + 'setup_intent=' + error.setup_intent.id;
                        } else if (intentId) {
                            // Use the intent ID we extracted earlier
                            if (isSetupIntent) {
                                successUrl += separator + 'setup_intent=' + intentId;
                            } else {
                                successUrl += separator + 'payment_intent=' + intentId;
                            }
                        }
                        
                        submitButton.textContent = 'Redirecting...';
                        window.location.href = successUrl;
                        return;
                    }
                    
                    const errorDiv = document.getElementById('stripe-errors');
                    if (errorDiv) {
                        errorDiv.textContent = error.message;
                        errorDiv.classList.remove('hidden');
                    }
                    isSubmitting = false;
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                } else {
                    const intent = paymentIntent || setupIntent;
                    let successUrl = config.successUrl || window.location.origin + '/member/subscription/success';
                    
                    // Add intent ID to URL for verification
                    if (intent && intent.id) {
                        const separator = successUrl.includes('?') ? '&' : '?';
                        if (paymentIntent) {
                            successUrl += separator + 'payment_intent=' + intent.id;
                        } else if (setupIntent) {
                            successUrl += separator + 'setup_intent=' + intent.id;
                        }
                    }
                    
                    if (intent && (intent.status === 'succeeded' || intent.status === 'processing')) {
                        window.location.href = successUrl;
                    } else {
                        submitButton.textContent = 'Redirecting...';
                        setTimeout(() => {
                            window.location.href = successUrl;
                        }, 1000);
                    }
                }
            } catch (err) {
                console.error('Stripe payment error:', err);
                
                // Handle payment_intent_unexpected_state in catch block too
                if (err.code === 'payment_intent_unexpected_state' || 
                    err.code === 'setup_intent_unexpected_state' ||
                    err.message?.includes('already succeeded') ||
                    err.message?.includes('already confirmed')) {
                    // Payment already succeeded, redirect to success
                    let successUrl = config.successUrl || window.location.origin + '/member/subscription/success';
                    const separator = successUrl.includes('?') ? '&' : '?';
                    
                    if (err.payment_intent?.id) {
                        successUrl += separator + 'payment_intent=' + err.payment_intent.id;
                    } else if (err.setup_intent?.id) {
                        successUrl += separator + 'setup_intent=' + err.setup_intent.id;
                    } else if (intentId) {
                        // Use the intent ID we extracted earlier
                        if (isSetupIntent) {
                            successUrl += separator + 'setup_intent=' + intentId;
                        } else {
                            successUrl += separator + 'payment_intent=' + intentId;
                        }
                    }
                    
                    submitButton.textContent = 'Redirecting...';
                    window.location.href = successUrl;
                    return;
                }
                
                const errorDiv = document.getElementById('stripe-errors');
                if (errorDiv) {
                    const errorMessage = err.message || 'An unexpected error occurred. Please try again.';
                    errorDiv.textContent = errorMessage;
                    errorDiv.classList.remove('hidden');
                }
                isSubmitting = false;
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeForm);
    } else {
        initializeForm();
    }
})();
