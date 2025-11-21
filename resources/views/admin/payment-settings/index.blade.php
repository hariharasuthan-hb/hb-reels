@extends('admin.layouts.app')

@section('page-title', 'Payment Settings')

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-lg font-semibold text-gray-900 mb-1">Payment Settings</h1>
            <p class="text-sm text-gray-600">Configure payment gateway settings for Stripe, Razorpay, and Google Pay</p>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
        <div class="alert alert-success animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger animate-fade-in">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div>
                <p class="font-semibold">Please fix the following errors:</p>
                <ul class="mt-1 list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    {{-- Payment Settings Form --}}
    <form action="{{ route('admin.payment-settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- Stripe Settings --}}
        <div class="admin-card">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                    <h2 class="text-base font-semibold text-gray-900">Stripe Payment Gateway</h2>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" 
                           name="enable_stripe" 
                           value="1"
                           {{ old('enable_stripe', $settings->enable_stripe) ? 'checked' : '' }}
                           class="sr-only peer"
                           id="enable_stripe"
                           onchange="toggleStripeFields()">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-700">Enable Stripe</span>
                </label>
            </div>
            
            <div id="stripe_fields" class="space-y-4 {{ old('enable_stripe', $settings->enable_stripe) ? '' : 'hidden' }}">
                <div>
                    <label for="stripe_publishable_key" class="block text-sm font-semibold text-gray-700 mb-2">
                        Stripe Publishable Key <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="stripe_publishable_key" 
                           id="stripe_publishable_key"
                           value="{{ old('stripe_publishable_key', $settings->stripe_publishable_key) }}"
                           placeholder="pk_test_..."
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('stripe_publishable_key') border-red-500 @enderror">
                    @error('stripe_publishable_key')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="stripe_secret_key" class="block text-sm font-semibold text-gray-700 mb-2">
                        Stripe Secret Key <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           name="stripe_secret_key" 
                           id="stripe_secret_key"
                           value="{{ old('stripe_secret_key', $settings->stripe_secret_key) }}"
                           placeholder="sk_test_..."
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('stripe_secret_key') border-red-500 @enderror">
                    @error('stripe_secret_key')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Your secret key is encrypted and stored securely</p>
                </div>
            </div>
        </div>

        {{-- Razorpay Settings --}}
        <div class="admin-card">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-base font-semibold text-gray-900">Razorpay Payment Gateway</h2>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" 
                           name="enable_razorpay" 
                           value="1"
                           {{ old('enable_razorpay', $settings->enable_razorpay) ? 'checked' : '' }}
                           class="sr-only peer"
                           id="enable_razorpay"
                           onchange="toggleRazorpayFields()">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-700">Enable Razorpay</span>
                </label>
            </div>
            
            <div id="razorpay_fields" class="space-y-4 {{ old('enable_razorpay', $settings->enable_razorpay) ? '' : 'hidden' }}">
                <div>
                    <label for="razorpay_key_id" class="block text-sm font-semibold text-gray-700 mb-2">
                        Razorpay Key ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="razorpay_key_id" 
                           id="razorpay_key_id"
                           value="{{ old('razorpay_key_id', $settings->razorpay_key_id) }}"
                           placeholder="rzp_test_..."
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('razorpay_key_id') border-red-500 @enderror">
                    @error('razorpay_key_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="razorpay_key_secret" class="block text-sm font-semibold text-gray-700 mb-2">
                        Razorpay Key Secret <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           name="razorpay_key_secret" 
                           id="razorpay_key_secret"
                           value="{{ old('razorpay_key_secret', $settings->razorpay_key_secret) }}"
                           placeholder="Enter your Razorpay secret key"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('razorpay_key_secret') border-red-500 @enderror">
                    @error('razorpay_key_secret')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Your secret key is encrypted and stored securely</p>
                </div>
            </div>
        </div>

        {{-- Google Pay Settings --}}
        <div class="admin-card">
            <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-200">
                <div class="flex items-center">
                    <svg class="w-6 h-6 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <h2 class="text-base font-semibold text-gray-900">Google Pay (UPI)</h2>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" 
                           name="enable_gpay" 
                           value="1"
                           {{ old('enable_gpay', $settings->enable_gpay) ? 'checked' : '' }}
                           class="sr-only peer"
                           id="enable_gpay"
                           onchange="toggleGpayFields()">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                    <span class="ml-3 text-sm font-medium text-gray-700">Enable Google Pay</span>
                </label>
            </div>
            
            <div id="gpay_fields" class="space-y-4 {{ old('enable_gpay', $settings->enable_gpay) ? '' : 'hidden' }}">
                <div>
                    <label for="gpay_upi_id" class="block text-sm font-semibold text-gray-700 mb-2">
                        Google Pay UPI ID <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="gpay_upi_id" 
                           id="gpay_upi_id"
                           value="{{ old('gpay_upi_id', $settings->gpay_upi_id) }}"
                           placeholder="yourname@upi"
                           class="w-full px-4 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('gpay_upi_id') border-red-500 @enderror">
                    @error('gpay_upi_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-xs text-gray-500">Enter your Google Pay UPI ID (e.g., yourname@upi)</p>
                </div>
            </div>
        </div>

        {{-- Submit Button --}}
        <div class="flex justify-end gap-3">
            <button type="submit" class="btn btn-primary">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function toggleStripeFields() {
    const checkbox = document.getElementById('enable_stripe');
    const fields = document.getElementById('stripe_fields');
    if (checkbox.checked) {
        fields.classList.remove('hidden');
    } else {
        fields.classList.add('hidden');
    }
}

function toggleRazorpayFields() {
    const checkbox = document.getElementById('enable_razorpay');
    const fields = document.getElementById('razorpay_fields');
    if (checkbox.checked) {
        fields.classList.remove('hidden');
    } else {
        fields.classList.add('hidden');
    }
}

function toggleGpayFields() {
    const checkbox = document.getElementById('enable_gpay');
    const fields = document.getElementById('gpay_fields');
    if (checkbox.checked) {
        fields.classList.remove('hidden');
    } else {
        fields.classList.add('hidden');
    }
}
</script>
@endpush

