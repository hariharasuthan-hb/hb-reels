@extends('admin.layouts.app')

@section('page-title', 'Edit User')

@section('content')
<div class="container mx-auto px-4 py-8 max-w-5xl">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-lg font-semibold text-gray-900">Edit User</h1>
        </div>
        <a href="{{ route('admin.users.index') }}" 
           class="inline-flex items-center px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200 font-medium">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Back to Users
        </a>
    </div>

    {{-- Error Messages --}}
    @if($errors->any())
        <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-sm">
            <div class="flex">
                <svg class="h-5 w-5 text-red-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-red-800 mb-2">Please fix the following errors:</h3>
                    <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    {{-- Form Card --}}
    <form action="{{ route('admin.users.update', $user->id) }}" method="POST" 
          class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
        @csrf
        @method('PUT')
        
        <div class="p-8">
            @include('admin.users._form', ['user' => $user, 'roles' => $roles, 'isEdit' => true])
        </div>

        {{-- Form Actions --}}
        <div class="bg-gray-50 px-8 py-6 border-t border-gray-200 flex justify-end space-x-4">
            <a href="{{ route('admin.users.index') }}" 
               class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                      font-medium transition duration-200">
                Cancel
            </a>
            <button type="submit" 
                    class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg 
                           hover:from-blue-700 hover:to-blue-800 font-medium shadow-md hover:shadow-lg 
                           transition duration-200 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Update User
            </button>
        </div>
    </form>
</div>
@endsection
