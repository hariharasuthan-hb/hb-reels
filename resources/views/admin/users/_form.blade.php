@php
    $user = $user ?? null;
    $roles = $roles ?? [];
    $isEdit = $isEdit ?? false;
@endphp

<div class="space-y-4">
    {{-- Personal Information Section --}}
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-5 rounded-xl border border-blue-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Personal Information
        </h3>
        
        <div class="grid grid-cols-2 gap-4">
            @include('admin.components.form-input', [
                'name' => 'name',
                'label' => 'Full Name',
                'value' => $user->name ?? null,
                'required' => true,
                'placeholder' => 'Enter full name',
            ])
            
            @include('admin.components.form-input', [
                'name' => 'email',
                'label' => 'Email Address',
                'type' => 'email',
                'value' => $user->email ?? null,
                'required' => true,
                'placeholder' => 'user@example.com',
            ])
            
            @include('admin.components.form-input', [
                'name' => 'phone',
                'label' => 'Phone Number',
                'type' => 'tel',
                'value' => $user->phone ?? null,
                'placeholder' => '+1 (555) 123-4567',
            ])
            
            @include('admin.components.form-input', [
                'name' => 'age',
                'label' => 'Age',
                'type' => 'number',
                'value' => $user->age ?? null,
                'placeholder' => '25',
                'attributes' => ['min' => '1', 'max' => '120'],
            ])
            
            @include('admin.components.form-select', [
                'name' => 'gender',
                'label' => 'Gender',
                'options' => ['' => 'Select Gender', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'],
                'value' => $user->gender ?? null,
                'placeholder' => 'Select Gender',
            ])
            
            @include('admin.components.form-textarea', [
                'name' => 'address',
                'label' => 'Address',
                'value' => $user->address ?? null,
                'placeholder' => 'Enter full address',
                'rows' => 2,
                'colspan' => 1,
            ])
        </div>
    </div>

    {{-- Account Security Section --}}
    <div class="bg-gradient-to-r from-green-50 to-emerald-50 p-5 rounded-xl border border-green-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Account Security
        </h3>
        
        <div class="grid grid-cols-2 gap-4">
            @if($isEdit)
                @include('admin.components.form-input', [
                    'name' => 'password',
                    'label' => 'New Password',
                    'type' => 'password',
                    'placeholder' => 'Enter new password',
                ])
                
                @include('admin.components.form-input', [
                    'name' => 'password_confirmation',
                    'label' => 'Confirm New Password',
                    'type' => 'password',
                    'placeholder' => 'Confirm new password',
                ])
            @else
                @include('admin.components.form-input', [
                    'name' => 'password',
                    'label' => 'Password',
                    'type' => 'password',
                    'required' => true,
                    'placeholder' => 'Enter password',
                ])
                
                @include('admin.components.form-input', [
                    'name' => 'password_confirmation',
                    'label' => 'Confirm Password',
                    'type' => 'password',
                    'required' => true,
                    'placeholder' => 'Confirm password',
                ])
            @endif
        </div>
    </div>

    {{-- Roles & Permissions Section --}}
    <div class="bg-gradient-to-r from-purple-50 to-pink-50 p-5 rounded-xl border border-purple-100">
        <h3 class="text-base font-semibold text-gray-800 mb-3 flex items-center">
            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            Role & Permissions
        </h3>
        
        @include('admin.components.form-radio-group', [
            'name' => 'role',
            'label' => 'Assign Role',
            'options' => collect($roles)->pluck('name', 'id')->toArray(),
            'value' => $isEdit && $user->roles->isNotEmpty() ? $user->roles->first()->id : null,
        ])

        <div class="mt-4">
            @include('admin.components.form-select', [
                'name' => 'status',
                'label' => 'Account Status',
                'options' => ['active' => 'Active', 'inactive' => 'Inactive'],
                'value' => $user->status ?? 'active',
                'required' => true,
            ])
        </div>
    </div>
</div>

