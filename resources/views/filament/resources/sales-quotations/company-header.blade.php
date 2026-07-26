<x-company-document-header
    :settings="$settings"
    document-title="عرض سعر"
    :document-number="$record->quotation_number"
    :document-date="$record->quotation_date->format('d/m/Y')"
/>
