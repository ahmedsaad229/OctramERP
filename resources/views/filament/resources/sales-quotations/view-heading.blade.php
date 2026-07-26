<div class="flex flex-wrap items-center gap-3">
    <span class="flex size-10 items-center justify-center rounded-full bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
        <x-filament::icon icon="heroicon-o-document-text" class="size-6" />
    </span>

    <span>عرض سعر</span>

    <x-filament::badge color="info">
        {{ $record->quotation_number }}
    </x-filament::badge>

    <x-filament::badge
        :color="match ($record->conversionStatus()) {
            \App\Models\SalesQuotation::STATUS_FULLY_CONVERTED => 'success',
            \App\Models\SalesQuotation::STATUS_PARTIALLY_CONVERTED => 'warning',
            default => $record->isExpired() ? 'danger' : 'success',
        }"
    >
        @if ($record->conversionStatus() === \App\Models\SalesQuotation::STATUS_FULLY_CONVERTED)
            محول بالكامل
        @elseif ($record->conversionStatus() === \App\Models\SalesQuotation::STATUS_PARTIALLY_CONVERTED)
            محول جزئيًا
        @elseif ($record->isExpired())
            منتهي
        @else
            ساري
        @endif
    </x-filament::badge>
</div>
