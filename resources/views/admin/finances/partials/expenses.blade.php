<div class="flex flex-col text-right">
    <span class="font-semibold text-gray-900">${{ number_format($amount, 2) }}</span>
    @if($amount > 0)
        <span class="text-xs text-gray-500">Avg daily: ${{ number_format($avgDaily, 2) }}</span>
    @endif
</div>

