@extends('frontend.layouts.app')

@section('content')
<style>
    #payment-information-card {
        position: relative;
        overflow: hidden;
        isolation: isolate;
    }
    #payment-information-card #stripe-payment-element {
        position: relative !important;
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        overflow: hidden !important;
    }
    #payment-information-card .payment-form-wrapper,
    #payment-information-card form#subscription-form {
        width: 100%;
        position: relative;
        box-sizing: border-box;
        overflow: hidden;
        display: block;
    }
    #payment-information-card .StripeElement,
    #payment-information-card iframe,
    #payment-information-card [class*="Stripe"],
    #payment-information-card [id*="stripe"],
    #payment-information-card [class*="stripe"] {
        width: 100% !important;
        max-width: 100% !important;
        box-sizing: border-box !important;
        position: relative !important;
    }
</style>

<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <a href="{{ route('member.dashboard') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Back to Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Subscribe to Plan</h1>
            <p class="mt-2 text-gray-600">Complete your subscription to get started</p>
        </div>

        {{-- Error Messages --}}
        @if(session('error') || $errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        @if(session('error'))
                            <p class="text-sm text-red-700 font-medium">{{ session('error') }}</p>
                        @endif
                        @if($errors->any())
                            <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Plan Details Card --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-lg p-6 sticky top-8">
                    <div class="text-center mb-6">
                        @if($plan->image)
                            <img src="{{ asset('storage/' . $plan->image) }}" alt="{{ $plan->plan_name }}" class="w-32 h-32 mx-auto rounded-lg object-cover mb-4">
                        @else
                            <div class="w-32 h-32 mx-auto bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center mb-4">
                                <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @endif
                        <h2 class="text-2xl font-bold text-gray-900">{{ $plan->plan_name }}</h2>
                        <p class="text-gray-600 mt-2">{{ $plan->description }}</p>
                    </div>

                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-gray-600">Price</span>
                            <span class="text-3xl font-bold text-gray-900">₹{{ number_format($plan->price, 2) }}</span>
                        </div>
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-gray-600">Duration</span>
                            <span class="text-gray-900 font-medium">{{ $plan->formatted_duration }}</span>
                        </div>

                        @if(!empty($hasTrial) && $hasTrial && !empty($trialDays) && $trialDays > 0)
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span class="text-sm font-medium text-green-800">
                                        {{ $trialDays }} days free trial
                                    </span>
                                </div>
                                <p class="text-xs text-green-700 mt-2">Card required. No charge during trial.</p>
                            </div>
                        @endif

                        @if(!empty($plan->features) && count($plan->features) > 0)
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-semibold text-gray-900 mb-3">Features</h3>
                                <ul class="space-y-2">
                                    @foreach($plan->features as $feature)
                                        @if(!empty($feature))
                                            <li class="flex items-start">
                                                <svg class="w-5 h-5 text-green-500 mr-2 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                </svg>
                                                <span class="text-sm text-gray-700">{{ $feature }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Payment Form Card --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-lg p-8" id="payment-information-card">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Payment Information</h2>

                    {{-- Gateway Selection (if multiple) --}}
                    @if(!empty($availableGateways) && count($availableGateways) > 1)
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Choose Payment Method</label>
                            <div class="grid grid-cols-2 gap-4">
                                @if(isset($availableGateways['stripe']))
                                    <button type="button" onclick="selectGateway('stripe')" id="gateway-stripe" class="gateway-btn p-4 border-2 border-gray-300 rounded-lg hover:border-blue-500 transition-colors text-left">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M13.976 9.15c-2.172-.806-3.356-1.426-3.356-2.409 0-.831.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l-2.541 4.725c-.49-.245-1.196-.642-2.094-.642zm3.092 8.403c-2.5-.933-3.352-1.538-3.352-2.41 0-.622.51-.978 1.423-.978 1.667 0 3.379.859 4.558 1.514l-2.629 4.874zm-8.89-5.284c-2.172-.806-3.356-1.426-3.356-2.408 0-.83.683-1.305 1.901-1.305 2.227 0 4.515.858 6.09 1.631l-2.54 4.725c-.49-.245-1.196-.642-2.094-.642zM24 11.314C24 8.048 21.865.314 13.976.314S0 8.048 0 11.314c0 3.266 2.135 10.999 10.024 10.999S24 14.58 24 11.314z"/>
                                            </svg>
                                            <span class="font-medium">Stripe</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Card @if(!empty($enableGpay) && $enableGpay), Google Pay @endif
                                        </p>
                                    </button>
                                @endif

                                @if(isset($availableGateways['razorpay']))
                                    <button type="button" onclick="selectGateway('razorpay')" id="gateway-razorpay" class="gateway-btn p-4 border-2 border-gray-300 rounded-lg hover:border-blue-500 transition-colors text-left">
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                            </svg>
                                            <span class="font-medium">Razorpay</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            Card, UPI @if(!empty($enableGpay) && $enableGpay), Google Pay @endif
                                        </p>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Payment Forms --}}
                    @php
                        $paymentData = session('payment_data', []);
                        $hasClientSecret = isset($paymentData['client_secret']) && !empty($paymentData['client_secret']);
                        $isStripeGateway = isset($paymentData['gateway']) && $paymentData['gateway'] === 'stripe';
                    @endphp

                    {{-- Stripe Payment Element (when client secret exists) --}}
                    @if($hasClientSecret && $isStripeGateway && isset($availableGateways['stripe']))
                        <form id="subscription-form" class="payment-form-wrapper">
                            <div id="stripe-payment-element" class="mb-6" style="min-height: 200px; width: 100%;"></div>
                            <div id="stripe-errors" class="text-red-600 text-sm mt-2 mb-4 hidden"></div>
                            <button type="submit" id="stripe-submit-btn" class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                @if(!empty($hasTrial) && $hasTrial && !empty($trialDays) && $trialDays > 0)
                                    Start {{ $trialDays }}-Day Free Trial
                                @else
                                    Subscribe Now - ₹{{ number_format($plan->price, 2) }}
                                @endif
                            </button>
                        </form>

                    @else
                        {{-- Default create subscription form that starts checkout flow --}}
                        <form id="subscription-form" action="{{ route('member.subscription.create', $plan->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="gateway" id="selected-gateway" value="{{ !empty($availableGateways) ? array_key_first($availableGateways) : 'stripe' }}" required>

                            @if(isset($availableGateways['stripe']))
                                <div id="stripe-payment-form" class="payment-form" style="display: {{ (array_key_first($availableGateways) === 'stripe') ? 'block' : 'none' }};">
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                        <p class="text-sm text-blue-800">Click the button below to set up your payment method. You'll enter your card details on the next step.</p>
                                    </div>
                                    <button type="submit" class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                        Continue to Payment
                                    </button>
                                </div>
                            @endif

                            @if(isset($availableGateways['razorpay']))
                                <div id="razorpay-payment-form" class="payment-form" style="display: {{ (array_key_first($availableGateways) === 'razorpay') ? 'block' : 'none' }};">
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                                        <p class="text-sm text-blue-800">Click the button below to proceed with Razorpay payment.</p>
                                    </div>
                                    <button type="submit" class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                        Continue to Payment
                                    </button>
                                </div>
                            @endif
                        </form>
                    @endif

                    {{-- Razorpay post-setup/buttons --}}
                    @if(isset($availableGateways['razorpay']) && session('payment_data'))
                        @php
                            $paymentDataSession = session('payment_data');
                        @endphp

                        @if(isset($paymentDataSession['payment_link_url']))
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                                <p class="text-sm text-green-800 mb-3">Click the button below to set up your payment method for the trial period.</p>
                                <a href="{{ $paymentDataSession['payment_link_url'] }}" target="_blank" class="inline-block bg-green-600 text-white py-2 px-4 rounded-lg font-medium hover:bg-green-700 transition-colors">
                                    Setup Payment Method
                                </a>
                            </div>
                        @elseif(isset($paymentDataSession['order_id']))
                            <div id="razorpay-checkout">
                                <button type="button" id="razorpay-btn" class="w-full bg-blue-600 text-white py-3 px-6 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                    Pay with Razorpay - ₹{{ number_format($paymentDataSession['amount'] ?? $plan->price, 2) }}
                                </button>
                            </div>
                        @endif
                    @endif

                    @if(!empty($hasTrial) && $hasTrial && !empty($trialDays) && $trialDays > 0)
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">
                                <strong>Free for {{ $trialDays }} days</strong>, then auto-renews at <strong>₹{{ number_format($plan->price, 2) }}</strong> per {{ $plan->duration_type }}.
                                You can cancel anytime before the trial ends.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@php
    // Single session read for payment_data (already used above)
    $paymentDataCheck = session('payment_data', []);
    $hasClientSecretCheck = isset($paymentDataCheck['client_secret']) && !empty($paymentDataCheck['client_secret']);
    $isStripeGatewayCheck = isset($paymentDataCheck['gateway']) && $paymentDataCheck['gateway'] === 'stripe';
@endphp

{{-- Stripe JS init --}}
@if(isset($availableGateways['stripe']) && $hasClientSecretCheck && $isStripeGatewayCheck)
    @php
        $paymentSettings = \App\Models\PaymentSetting::getSettings();
        $paymentData = session('payment_data');
    @endphp
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        window.stripeCheckoutConfig = {
            publishableKey: '{{ $paymentSettings->stripe_publishable_key }}',
            clientSecret: '{{ $paymentData['client_secret'] }}',
            successUrl: '{{ route('member.subscription.success') }}',
        };
    </script>
    <script src="{{ asset('js/stripe-checkout.js') }}?v={{ filemtime(public_path('js/stripe-checkout.js')) }}"></script>
@endif

{{-- Razorpay JS init --}}
@if(isset($availableGateways['razorpay']) && session('payment_data') && isset(session('payment_data')['order_id']))
    @php
        $paymentData = session('payment_data');
        $paymentSettings = \App\Models\PaymentSetting::getSettings();
        $razorpayOptions = [
            'key' => $paymentSettings->razorpay_key_id,
            'amount' => (int)($paymentData['amount'] * 100),
            'currency' => $paymentData['currency'] ?? 'INR',
            'order_id' => $paymentData['order_id'],
            'name' => $plan->plan_name,
            'description' => $plan->description,
            'prefill' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'contact' => auth()->user()->phone ?? '',
            ],
            'theme' => ['color' => '#3B82F6'],
        ];
        if (!empty($enableGpay)) {
            $razorpayOptions['method'] = [
                'googlepay' => true,
                'upi' => true,
                'card' => true,
            ];
        }
    @endphp
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('razorpay-btn');
            if (btn) {
                btn.addEventListener('click', function() {
                    const options = @json($razorpayOptions);
                    options.handler = function(response) {
                        window.location.href = '{{ route('member.subscription.success') }}?razorpay_payment_id=' + response.razorpay_payment_id;
                    };
                    options.modal = options.modal || {};
                    options.modal.ondismiss = function() {};
                    const rzp = new Razorpay(options);
                    rzp.open();
                });
            }
        });
    </script>
@endif

<script>
    function selectGateway(gateway) {
        const selectedInput = document.getElementById('selected-gateway');
        if (selectedInput) selectedInput.value = gateway;

        document.querySelectorAll('.payment-form').forEach(form => {
            form.style.display = 'none';
        });

        const activeForm = document.getElementById(gateway + '-payment-form');
        if (activeForm) activeForm.style.display = 'block';

        document.querySelectorAll('.gateway-btn').forEach(btn => {
            btn.classList.remove('border-blue-500', 'bg-blue-50');
            btn.classList.add('border-gray-300');
        });

        const selectedBtn = document.getElementById('gateway-' + gateway);
        if (selectedBtn) {
            selectedBtn.classList.remove('border-gray-300');
            selectedBtn.classList.add('border-blue-500', 'bg-blue-50');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        @if(!empty($availableGateways) && count($availableGateways) > 0)
            selectGateway('{{ array_key_first($availableGateways) }}');
        @endif
    });
</script>
@endsection
