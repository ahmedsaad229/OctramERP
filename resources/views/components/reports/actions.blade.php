@props([
    'excelUrl' => null,
    'printUrl' => null,
    'resetMethod' => 'resetReport',
])

<div class="octram-report-actions">
    <x-filament::button type="submit" icon="heroicon-o-funnel">
        تطبيق عوامل التصفية
    </x-filament::button>

    <x-filament::button
        type="button"
        color="gray"
        icon="heroicon-o-arrow-path"
        :wire:click="$resetMethod"
    >
        إعادة تعيين
    </x-filament::button>

    @if ($excelUrl)
        <x-filament::button
            tag="a"
            :href="$excelUrl"
            color="success"
            icon="heroicon-o-arrow-down-tray"
            target="_blank"
            rel="noopener noreferrer"
        >
            تصدير Excel
        </x-filament::button>
    @endif

    @if ($printUrl)
        <x-filament::button
            tag="a"
            :href="$printUrl"
            icon="heroicon-o-printer"
            target="_blank"
            rel="noopener noreferrer"
        >
            طباعة
        </x-filament::button>
    @endif
</div>
