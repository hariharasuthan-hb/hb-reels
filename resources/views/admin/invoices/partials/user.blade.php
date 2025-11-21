<div class="flex flex-col">
    <span class="font-medium text-gray-900">{{ $invoice->user->name ?? 'Unknown User' }}</span>
    <span class="text-xs text-gray-500">{{ $invoice->user->email ?? '—' }}</span>
</div>

