@props([
    'settings',
    'documentTitle',
    'documentNumber',
    'documentDate',
    'expiryDate' => null,
])

<header {{ $attributes->class(['company-document-header']) }}>
    <div class="company-document-layout">

        <section class="company-document-details">
            <div class="company-document-title">
                {{ $documentTitle }}
            </div>

            <div class="company-document-meta">
                <div>
                    <span>رقم المستند</span>
                    <strong dir="ltr">{{ $documentNumber }}</strong>
                </div>

                <div>
                    <span>التاريخ</span>
                    <strong dir="ltr">{{ $documentDate }}</strong>
                </div>

                @if ($expiryDate)
                    <div>
                        <span>صالح حتى</span>
                        <strong dir="ltr">{{ $expiryDate }}</strong>
                    </div>
                @endif
            </div>
        </section>

        <section class="company-document-company">
            <div class="company-document-company-name">
                {{ $settings->commercialName() }}
            </div>

            @unless (
                str_contains(
                    $settings->commercialName(),
                    'للمقاولات والتوريدات'
                )
            )
                <div class="company-document-activity">
                    للمقاولات والتوريدات
                </div>
            @endunless

            <div class="company-document-contact-lines">
                @if (filled($settings->address))
                    <div>{{ $settings->address }}</div>
                @endif

                @if (filled($settings->phone) || filled($settings->mobile))
                    <div dir="ltr">
                        {{ collect([
                            $settings->phone,
                            $settings->mobile,
                        ])->filter()->join(' - ') }}
                    </div>
                @endif

                @if (filled($settings->email) || filled($settings->website))
                    <div dir="ltr">
                        {{ collect([
                            $settings->email,
                            $settings->website,
                        ])->filter()->join(' - ') }}
                    </div>
                @endif

                @if (
                    filled($settings->commercial_registry)
                    || filled($settings->tax_number)
                )
                    <div class="company-document-legal">
                        @if (filled($settings->commercial_registry))
                            <span>
                                سجل تجاري:
                                <b dir="ltr">
                                    {{ $settings->commercial_registry }}
                                </b>
                            </span>
                        @endif

                        @if (filled($settings->tax_number))
                            <span>
                                رقم ضريبي:
                                <b dir="ltr">
                                    {{ $settings->tax_number }}
                                </b>
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        <section class="company-document-logo-wrap">
            @if ($logoUrl = $settings->logoUrl())
                <img
                    src="{{ $logoUrl }}"
                    alt="{{ $settings->commercialName() }}"
                    class="company-document-logo"
                >
            @else
                <div class="company-document-logo-placeholder">
                    OCTRAM
                </div>
            @endif
        </section>
    </div>
</header>
