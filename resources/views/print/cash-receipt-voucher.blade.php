<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>سند استلام نقدية {{ $voucher->document_number }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef0f3; color: #1f2937; font-family: Arial, Tahoma, sans-serif; }
        .voucher { width: min(190mm, calc(100% - 32px)); margin: 24px auto; padding: 12mm; background: #fff; box-shadow: 0 6px 24px rgb(15 23 42 / 12%); }
        .toolbar { margin-bottom: 12px; } button { border: 0; border-radius: 6px; padding: 8px 16px; background: #2563eb; color: #fff; cursor: pointer; }
        .document-header { margin-bottom: 24px; border-bottom: 2px solid #2563eb; border-radius: 0; }
        .company-document-title { color: #1d4ed8; font-size: 24px; font-weight: 700; }
        .details { display: grid; gap: 14px; margin-top: 20px; font-size: 15px; }
        .row { display: grid; grid-template-columns: 150px 1fr; gap: 12px; padding-bottom: 10px; border-bottom: 1px solid #e5e7eb; }
        .label { color: #64748b; } .value { font-weight: 700; }
        .money { direction: ltr; unicode-bidi: isolate; white-space: nowrap; }
        .signatures { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-top: 55px; text-align: center; }
        @media print { body { background: #fff; } .voucher { width: auto; margin: 0; padding: 0; box-shadow: none; } .toolbar { display: none; } }
    </style>
</head>
<body>
    <main class="voucher">
        <div class="toolbar"><button type="button" onclick="window.print()">طباعة / حفظ PDF</button></div>
        <x-company-document-header class="document-header" :settings="$settings"
            document-title="سند استلام نقدية" :document-number="$voucher->document_number"
            :document-date="$voucher->date->format('d/m/Y')" />
        <section class="details">
            <div class="row"><div class="label">نوع الاستلام</div><div class="value">{{ $voucher->receipt_type_label }}</div></div>
            <div class="row"><div class="label">استلمنا من السيد / الجهة</div><div class="value">{{ $voucher->receivedFromName() }}</div></div>
            <div class="row"><div class="label">مبلغ وقدره</div><div class="value money">{{ \App\Support\ArabicMoney::format($voucher->amount) }}</div></div>
            <div class="row"><div class="label">الخزينة</div><div class="value">{{ $voucher->treasury->name }}</div></div>
            <div class="row"><div class="label">طريقة الاستلام</div><div class="value">{{ $voucher->payment_method->label() }}</div></div>
            <div class="row"><div class="label">الرقم المرجعي</div><div class="value">{{ $voucher->reference_number ?: '—' }}</div></div>
            <div class="row"><div class="label">وذلك عن</div><div class="value">{{ $voucher->notes ?: ($voucher->receipt_reason_label ?: '—') }}</div></div>
        </section>
        <div class="signatures"><div>المستلم<br><br>....................</div><div>الخزينة<br><br>....................</div><div>اعتماد<br><br>....................</div></div>
    </main>
</body>
</html>
