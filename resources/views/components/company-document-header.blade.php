@props([
    'settings',
    'documentTitle',
    'documentNumber',
    'documentDate',
    'expiryDate' => null,
])

<header {{ $attributes->class(['company-document-header flex flex-col gap-5 rounded-xl border border-gray-200 p-5 sm:flex-row sm:items-center sm:justify-between dark:border-white/10']) }}>
    <div class="company-document-company flex min-w-0 items-center gap-4">
        @if ($logoUrl = $settings->logoUrl())
            <img
                src="{{ $logoUrl }}"
                alt="{{ $settings->commercialName() }}"
                class="company-document-logo h-20 w-auto max-w-40 shrink-0 object-contain"
            >
        @endif

        <div class="min-w-0">
            <div class="company-document-company-name text-lg font-bold text-gray-950 dark:text-white">
                {{ $settings->commercialName() }}
            </div>
            @unless (str_contains($settings->commercialName(), 'للمقاولات والتوريدات'))
                <div class="company-document-activity mt-1 text-sm text-gray-500 dark:text-gray-400">
                    للمقاولات والتوريدات
                </div>
            @endunless
        </div>
    </div>

    <div class="company-document-details">
        <div class="company-document-title">{{ $documentTitle }}</div>
        <dl class="grid shrink-0 grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
        <dt class="text-gray-500 dark:text-gray-400">رقم المستند</dt>
        <dd class="font-semibold text-gray-950 dark:text-white" dir="ltr">{{ $documentNumber }}</dd>
        <dt class="text-gray-500 dark:text-gray-400">التاريخ</dt>
        <dd class="text-gray-950 dark:text-white" dir="ltr">{{ $documentDate }}</dd>
        @if ($expiryDate)
            <dt class="text-gray-500 dark:text-gray-400">صالح حتى</dt>
            <dd class="text-gray-950 dark:text-white" dir="ltr">{{ $expiryDate }}</dd>
        @endif
        </dl>
    </div>
</header>
