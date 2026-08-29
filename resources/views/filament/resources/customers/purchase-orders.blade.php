<style>
.octram-cpo-report {
    direction: rtl;
    font-family: inherit;
}

.octram-cpo-cards {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.octram-cpo-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 18px;
    box-shadow: 0 1px 3px rgba(0,0,0,.05);
}

.octram-cpo-card-label {
    font-size: 13px;
    color: #6b7280;
    margin-bottom: 8px;
}

.octram-cpo-card-value {
    font-size: 28px;
    line-height: 1;
    font-weight: 800;
    color: #111827;
}

.octram-cpo-card.total {
    border-top: 4px solid #2563eb;
}

.octram-cpo-card.open {
    border-top: 4px solid #16a34a;
}

.octram-cpo-card.delayed {
    border-top: 4px solid #dc2626;
}

.octram-cpo-card.completed {
    border-top: 4px solid #0891b2;
}

.octram-cpo-card.total .octram-cpo-card-value {
    color: #2563eb;
}

.octram-cpo-card.open .octram-cpo-card-value {
    color: #16a34a;
}

.octram-cpo-card.delayed .octram-cpo-card-value {
    color: #dc2626;
}

.octram-cpo-card.completed .octram-cpo-card-value {
    color: #0891b2;
}

.octram-cpo-box {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 1px 4px rgba(0,0,0,.06);
}

.octram-cpo-box-title {
    padding: 15px 18px;
    border-bottom: 1px solid #e5e7eb;
    font-weight: 800;
    color: #111827;
    font-size: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.octram-cpo-count {
    color: #6b7280;
    font-size: 12px;
    font-weight: 500;
}

.octram-cpo-scroll {
    max-height: 580px;
    overflow: auto;
}

.octram-cpo-table {
    width: 100%;
    min-width: 1250px;
    border-collapse: collapse;
    font-size: 13px;
}

.octram-cpo-table thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #0f5ea8;
    color: #fff;
    padding: 11px 10px;
    text-align: center;
    white-space: nowrap;
    font-weight: 700;
    border-left: 1px solid rgba(255,255,255,.18);
}

.octram-cpo-table tbody td {
    padding: 10px;
    border-bottom: 1px solid #edf0f3;
    text-align: center;
    white-space: nowrap;
    color: #374151;
}

.octram-cpo-table tbody tr:nth-child(even) {
    background: #f8fafc;
}

.octram-cpo-table tbody tr:hover {
    background: #eff6ff;
}

.octram-cpo-document {
    color: #1565c0;
    font-weight: 800;
    text-decoration: none;
}

.octram-cpo-document:hover {
    text-decoration: underline;
}

.octram-cpo-status {
    display: inline-block;
    min-width: 54px;
    padding: 4px 10px;
    border-radius: 999px;
    background: #dcfce7;
    color: #15803d;
    font-size: 12px;
    font-weight: 700;
}

.octram-cpo-late {
    display: inline-block;
    padding: 4px 9px;
    border-radius: 999px;
    background: #fee2e2;
    color: #b91c1c;
    font-weight: 700;
    font-size: 12px;
}

.octram-cpo-progress {
    display: flex;
    align-items: center;
    gap: 7px;
    min-width: 125px;
}

.octram-cpo-progress-track {
    height: 7px;
    flex: 1;
    background: #e5e7eb;
    border-radius: 20px;
    overflow: hidden;
}

.octram-cpo-progress-bar {
    height: 100%;
    background: #2563eb;
    border-radius: 20px;
}

.octram-cpo-progress-text {
    width: 48px;
    font-weight: 700;
    font-size: 11px;
    color: #4b5563;
}

.octram-cpo-empty {
    padding: 35px;
    border: 1px dashed #d1d5db;
    border-radius: 12px;
    text-align: center;
    color: #6b7280;
    background: #f9fafb;
}

.octram-cpo-footer {
    padding: 11px 18px;
    text-align: center;
    background: #f8fafc;
    border-top: 1px solid #e5e7eb;
    color: #64748b;
    font-size: 12px;
}

@media (max-width: 900px) {
    .octram-cpo-cards {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 520px) {
    .octram-cpo-cards {
        grid-template-columns: 1fr;
    }
}
</style>


@if(!$summary || $summary['rows']->isEmpty())

    <div class="octram-cpo-empty">
        لا توجد أوامر توريد مسجلة لهذا العميل.
    </div>

@else

<div class="octram-cpo-report">

    <div class="octram-cpo-cards">

        <div class="octram-cpo-card total">
            <div class="octram-cpo-card-label">إجمالي أوامر التوريد</div>
            <div class="octram-cpo-card-value">
                {{ number_format($summary['total']) }}
            </div>
        </div>

        <div class="octram-cpo-card open">
            <div class="octram-cpo-card-label">الأوامر المفتوحة</div>
            <div class="octram-cpo-card-value">
                {{ number_format($summary['open']) }}
            </div>
        </div>

        <div class="octram-cpo-card delayed">
            <div class="octram-cpo-card-label">الأوامر المتأخرة</div>
            <div class="octram-cpo-card-value">
                {{ number_format($summary['delayed']) }}
            </div>
        </div>

        <div class="octram-cpo-card completed">
            <div class="octram-cpo-card-label">الأوامر المكتملة</div>
            <div class="octram-cpo-card-value">
                {{ number_format($summary['completed']) }}
            </div>
        </div>

    </div>


    <div class="octram-cpo-box">

        <div class="octram-cpo-box-title">
            <span>تفاصيل أوامر التوريد</span>

            <span class="octram-cpo-count">
                {{ number_format($summary['total']) }} أمر توريد
            </span>
        </div>


        <div class="octram-cpo-scroll">

            <table class="octram-cpo-table">

                <thead>
                    <tr>
                        <th>رقم المستند</th>
                        <th>رقم أمر العميل</th>
                        <th>تاريخ الأمر</th>
                        <th>المشروع</th>
                        <th>تاريخ التسليم</th>
                        <th>الحالة</th>
                        <th>نسبة التنفيذ</th>
                        <th>عدد الأصناف</th>
                        <th>الكمية المتبقية</th>
                        <th>المرفقات</th>
                        <th>الفواتير</th>
                        <th>التأخير</th>
                    </tr>
                </thead>

                <tbody>

                @foreach($summary['rows'] as $row)

                    @php
                        $percentage = max(0, min(100, (float) $row['percentage']));
                    @endphp

                    <tr>

                        <td>
                            <a
                                href="{{ $row['url'] }}"
                                class="octram-cpo-document"
                            >
                                {{ $row['documentNumber'] }}
                            </a>
                        </td>

                        <td>
                            {{ filled($row['customerOrderNumber']) ? $row['customerOrderNumber'] : '—' }}
                        </td>

                        <td>
                            {{ filled($row['orderDate']) ? $row['orderDate'] : '—' }}
                        </td>

                        <td>
                            {{ filled($row['project']) ? $row['project'] : '—' }}
                        </td>

                        <td>
                            {{ filled($row['deliveryDate']) ? $row['deliveryDate'] : '—' }}
                        </td>

                        <td>
                            <span class="octram-cpo-status">
                                {{ $row['statusLabel'] }}
                            </span>
                        </td>

                        <td>
                            <div class="octram-cpo-progress">

                                <div class="octram-cpo-progress-track">
                                    <div
                                        class="octram-cpo-progress-bar"
                                        style="width: {{ $percentage }}%;"
                                    ></div>
                                </div>

                                <span class="octram-cpo-progress-text">
                                    {{ number_format($percentage, 2) }}%
                                </span>

                            </div>
                        </td>

                        <td>
                            {{ number_format($row['itemsCount']) }}
                        </td>

                        <td>
                            {{ number_format($row['remainingQuantity'], 2) }}
                        </td>

                        <td>
                            {{ number_format($row['attachmentCount']) }}
                        </td>

                        <td>
                            {{ number_format($row['invoiceCount']) }}
                        </td>

                        <td>
                            @if($row['delayed'])
                                <span class="octram-cpo-late">
                                    متأخر
                                </span>
                            @else
                                —
                            @endif
                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>


        <div class="octram-cpo-footer">
            إجمالي عدد أوامر التوريد:
            <strong>{{ number_format($summary['total']) }}</strong>
        </div>

    </div>

</div>

@endif
