<!DOCTYPE html>
<html lang="ar" dir="rtl"><head><meta charset="utf-8"><title>حركة صنف</title>
<style>
@page{size:A4 landscape;margin:8mm}*{box-sizing:border-box}body{font-family:Arial,Tahoma,sans-serif;color:#111827;font-size:10px}.toolbar{margin-bottom:10px}button{padding:7px 14px}table{width:100%;border-collapse:collapse;margin-top:12px}thead{display:table-header-group}tr{break-inside:avoid}th,td{border:1px solid #d1d5db;padding:5px;text-align:center}.summary{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:10px 0}.box{border:1px solid #d1d5db;padding:7px}.ltr{direction:ltr;unicode-bidi:isolate}@media print{.toolbar{display:none}}
</style></head><body>
<div class="toolbar"><button onclick="window.print()">طباعة / حفظ PDF</button></div>
<x-company-document-header :settings="$settings" document-title="حركة صنف" :document-number="$report['item']->code" :document-date="now()->format('d/m/Y')" />
<p><strong>الصنف:</strong> {{ $report['item']->name }} — <strong>الوحدة:</strong> {{ $report['item']->unit?->name ?: '—' }} — <strong>المخزن:</strong> {{ $report['warehouse']?->name ?: 'كل المخازن' }}</p>
<p><strong>الفترة:</strong> {{ $report['fromDate']?->format('d/m/Y') ?: 'بداية الحركة' }} — {{ $report['toDate']?->format('d/m/Y') ?: 'حتى تاريخه' }}</p>
<div class="summary">
@foreach (['الرصيد الافتتاحي'=>$report['openingQuantity'],'قيمة الافتتاحي'=>$report['openingValue'],'إجمالي الوارد'=>$report['totalInbound'],'إجمالي المنصرف'=>$report['totalOutbound'],'الرصيد الختامي'=>$report['closingQuantity'],'قيمة الختامي'=>$report['closingValue'],'متوسط التكلفة'=>$report['closingAverage'],'عدد الحركات'=>$report['transactionCount']] as $label=>$value)
<div class="box"><strong>{{ $label }}</strong><div class="ltr">{{ is_numeric($value) ? number_format((float)$value, 2) : $value }}</div></div>
@endforeach
</div>
<table><thead><tr>@foreach(['التاريخ','نوع المستند','رقم المستند','المخزن','البيان','وارد','منصرف','الرصيد','تكلفة الوحدة','قيمة الحركة','قيمة الرصيد','متوسط التكلفة'] as $h)<th>{{ $h }}</th>@endforeach</tr></thead><tbody>
@foreach($report['rows'] as $row)<tr><td>{{ $row['date'] }}</td><td>{{ $row['typeLabel'] }}</td><td class="ltr">{{ $row['reference'] }}</td><td>{{ $row['warehouse'] }}</td><td>{{ $row['description'] }}</td><td>{{ number_format($row['inbound'],2) }}</td><td>{{ number_format($row['outbound'],2) }}</td><td>{{ number_format($row['runningQuantity'],2) }}</td><td>{{ number_format($row['unitCost'],2) }}</td><td>{{ number_format($row['movementValue'],2) }}</td><td>{{ number_format($row['runningValue'],2) }}</td><td>{{ number_format($row['runningAverage'],2) }}</td></tr>@endforeach
</tbody></table></body></html>
