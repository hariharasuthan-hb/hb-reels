<div class="flex flex-col">
    <span class="font-medium text-gray-900">{{ $payment->user->name ?? 'Unknown User' }}</span>
    <span class="text-xs text-gray-500">{{ $payment->user->email ?? '—' }}</span>
</div>

