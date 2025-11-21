@php
    $subscriptionPlan = $subscriptionPlan ?? null;
    $isEdit = $isEdit ?? false;
    $features = \App\Models\SubscriptionPlan::getFeaturesForForm($subscriptionPlan);
    $isActive = \App\Models\SubscriptionPlan::getDefaultIsActive($subscriptionPlan);
@endphp
@php use Illuminate\Support\Facades\Storage; @endphp

<div class="space-y-4">
    {{-- Plan Information Section --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-5 rounded-xl border border-blue-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Plan Information
        </h3>
        
        <div class="grid grid-cols-2 gap-4">
            @include('admin.components.form-input', [
                'name' => 'plan_name',
                'label' => 'Plan Name',
                'value' => $subscriptionPlan->plan_name ?? null,
                'required' => true,
                'placeholder' => 'e.g., Basic Monthly Plan',
            ])
            
            @include('admin.components.form-input', [
                'name' => 'price',
                'label' => 'Price',
                'type' => 'number',
                'value' => $subscriptionPlan->price ?? null,
                'required' => true,
                'placeholder' => '0.00',
                'attributes' => ['step' => '0.01', 'min' => '0'],
            ])
            
            @include('admin.components.form-select', [
                'name' => 'duration_type',
                'label' => 'Duration Type',
                'options' => \App\Models\SubscriptionPlan::getDurationTypeOptions(),
                'value' => old('duration_type', $subscriptionPlan->duration_type ?? null),
                'required' => true,
                'placeholder' => null,
            ])
            
            @include('admin.components.form-input', [
                'name' => 'duration',
                'label' => 'Duration',
                'type' => 'number',
                'value' => $subscriptionPlan->duration ?? null,
                'required' => true,
                'placeholder' => '1',
                'attributes' => ['min' => '1'],
            ])
            
            @include('admin.components.form-textarea', [
                'name' => 'description',
                'label' => 'Description',
                'value' => $subscriptionPlan->description ?? null,
                'placeholder' => 'Enter plan description',
                'rows' => 3,
                'colspan' => 2,
            ])
            
            {{-- Image Upload --}}
            <div class="md:col-span-2">
                <label for="image" class="block text-sm font-semibold text-gray-700 mb-2">
                    Plan Image
                </label>
                <div class="space-y-3">
                    <input type="file" 
                           name="image" 
                           id="image" 
                           accept="image/*"
                           onchange="previewImage(this)"
                           class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer border border-gray-300 rounded-lg p-2 @error('image') border-red-500 @enderror">
                    <p class="text-xs text-gray-500">Optional. JPG, PNG, GIF, or WebP. Max 5MB.</p>
                    @error('image')
                        <p class="text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    
                    @if($subscriptionPlan && $subscriptionPlan->image)
                        <div class="mt-3">
                            <p class="text-xs text-gray-500 mb-2">Current Image:</p>
                            <img src="{{ Storage::disk('public')->url($subscriptionPlan->image) }}" 
                                 alt="Plan Image" 
                                 class="max-w-xs h-32 object-cover rounded-lg border border-gray-300">
                        </div>
                    @endif
                    
                    <div id="image-preview" class="mt-3 hidden">
                        <p class="text-xs text-gray-500 mb-2">Preview:</p>
                        <img id="preview-img" src="" alt="Preview" class="max-w-xs h-32 object-cover rounded-lg border border-gray-300">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Status Section --}}
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-5 rounded-xl border border-green-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Status
        </h3>
        
        <div class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="flex items-center">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1"
                           {{ $isActive ? 'checked' : '' }}
                           class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm font-medium text-gray-700">Active Plan</span>
                </label>
                <p class="mt-1 text-xs text-gray-500">Uncheck to deactivate this subscription plan</p>
            </div>
        </div>
    </div>

    {{-- Features Section --}}
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-5 rounded-xl border border-purple-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Plan Features
        </h3>
        
        <div id="features-container" class="space-y-2">
            @foreach($features as $index => $feature)
                <div class="feature-item flex items-center gap-2">
                    <input type="text" 
                           name="features[]" 
                           value="{{ old("features.{$index}", $feature) }}"
                           placeholder="Enter feature (e.g., Access to all equipment)"
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('features.*') border-red-500 @enderror">
                    @if($index > 0)
                        <button type="button" 
                                class="remove-feature text-red-600 hover:text-red-800 p-2"
                                title="Remove feature">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
        
        <button type="button" 
                id="add-feature" 
                class="mt-3 text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center">
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Feature
        </button>
        
        @error('features.*')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('features-container');
    const addButton = document.getElementById('add-feature');
    
    // Add feature
    addButton.addEventListener('click', function() {
        const featureItem = document.createElement('div');
        featureItem.className = 'feature-item flex items-center gap-2';
        featureItem.innerHTML = `
            <input type="text" 
                   name="features[]" 
                   value=""
                   placeholder="Enter feature (e.g., Access to all equipment)"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <button type="button" 
                    class="remove-feature text-red-600 hover:text-red-800 p-2"
                    title="Remove feature">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        `;
        container.appendChild(featureItem);
    });
    
    // Remove feature
    container.addEventListener('click', function(e) {
        if (e.target.closest('.remove-feature')) {
            e.target.closest('.feature-item').remove();
        }
    });
});

// Image preview function
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
        }
        
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('hidden');
    }
}
</script>
@endpush

