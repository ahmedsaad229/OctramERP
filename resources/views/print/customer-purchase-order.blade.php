<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>أمر توريد عميل</title><style>
@page{size:A4 landscape;margin:10mm}body{font-family:Arial,Tahoma,sans-serif;color:#111827;font-size:12px}.toolbar{margin-bottom:12px}table{width:100%;border-collapse:collapse;margin-top:16px}thead{display:table-header-group}tr{break-inside:avoid}th,td{border:1px solid #d1d5db;padding:7px;text-align:center}.meta{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:14px 0}.box{border:1px solid #ddd;padding:8px}.ltr{direction:ltr}@media print{.toolbar{display:none}}
</style></head><body><div class="toolbar"><button onclick="window.print()">طباعة / حفظ PDF</button></div>
<x-company-document-header :settings="$settings" document-title="أمر توريد عميل" :document-number="$order->document_number" :document-date="$order->order_date->format('d/m/Y')"/>
<div class="meta"><div class="box">رقم أمر العميل: <b>{{ $order->customer_order_number ?: '—' }}</b></div><div class="box">العميل: <b>{{ $order->customer->name }}</b></div><div class="box">المشروع: <b>{{ $order->project_name ?: '—' }}</b></div><div class="box">الحالة: <b>{{ \App\Models\CustomerPurchaseOrder::statusOptions()[$order->status] }}</b></div><div class="box">تاريخ الاستلام: {{ $order->received_date?->format('d/m/Y') ?: '—' }}</div><div class="box">التسليم المطلوب: {{ $order->required_delivery_date?->format('d/m/Y') ?: '—' }}</div><div class="box">مكان التسليم: {{ $order->delivery_location ?: '—' }}</div><div class="box">نسبة التنفيذ: {{ number_format($order->execution_percentage,2) }}%</div></div>
<table><thead><tr><th>الصنف</th><th>الوحدة</th><th>المطلوب</th><th>المنفذ</th><th>المتبقي</th><th>سعر الوحدة</th><th>الإجمالي</th><th>ملاحظات</th></tr></thead><tbody>
@foreach($order->items as $line)<tr><td>{{ $line->item->name }}</td><td>{{ $line->unit?->name ?: '—' }}</td><td>{{ $line->ordered_quantity }}</td><td>{{ $line->executed_quantity }}</td><td>{{ $line->remaining_quantity }}</td><td>{{ number_format((float)$line->unit_price,2) }} ج.م</td><td>{{ number_format((float)$line->line_total,2) }} ج.م</td><td>{{ $line->notes }}</td></tr>@endforeach
</tbody></table><p><strong>الملاحظات:</strong> {{ $order->notes ?: '—' }}</p><p>المرفقات: {{ $order->attachments->pluck('original_name')->join('، ') ?: 'لا توجد' }}</p>
@if(!empty($executionDocuments))
<h3>ملخص مستندات التنفيذ</h3><table><thead><tr><th>رقم فاتورة البيع</th><th>التاريخ</th><th>الكمية المنفذة</th><th>قيمة الفاتورة</th></tr></thead><tbody>
@foreach($executionDocuments as $document)<tr><td>{{ $document['reference'] }}</td><td>{{ $document['date'] }}</td><td>{{ $document['quantity'] }}</td><td>{{ number_format($document['total'],2) }} ج.م</td></tr>@endforeach
</tbody></table>
@endif
</body></html>
