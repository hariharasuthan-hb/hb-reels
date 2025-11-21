<div class="flex flex-col">
    <span class="font-medium text-gray-900">INV-{{ str_pad($invoice->id, 5, '0', STR_PAD_LEFT) }}</span>
    <span class="text-xs text-gray-500">{{ $invoice->transaction_id ?? '—' }}</span>
</div>

