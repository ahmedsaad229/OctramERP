<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseRequest;
use App\Models\SupplierPaymentVoucher;
use App\Models\SupplierPurchaseOrder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;

class PurchasingDocumentPrintController extends Controller
{
    public function purchaseRequest(PurchaseRequest $purchaseRequest): View
    {
        $this->safeLoad($purchaseRequest, [
            'items.item',
            'requestedBy',
        ]);

        return $this->render(
            document: $purchaseRequest,
            title: 'طلب شراء',
            number: $purchaseRequest->document_number
                ?? $purchaseRequest->request_number
                ?? (string) $purchaseRequest->getKey(),
            date: $purchaseRequest->request_date
                ?? $purchaseRequest->date
                ?? $purchaseRequest->created_at,
            partyLabel: 'الجهة الطالبة',
            partyName: $purchaseRequest->department
                ?? $purchaseRequest->requestedBy?->name
                ?? '—',
            meta: [
                'طالب الشراء' => $purchaseRequest->requestedBy?->name
                    ?? $purchaseRequest->requester_name
                    ?? '—',
                'القسم' => $purchaseRequest->department ?? '—',
                'الأولوية' => $purchaseRequest->priority ?? '—',
                'الحالة' => $purchaseRequest->status ?? '—',
            ],
            notes: $purchaseRequest->notes,
            items: $purchaseRequest->items ?? collect(),
            quantityFields: ['requested_quantity', 'quantity'],
            priceFields: ['estimated_unit_price', 'unit_price', 'unit_cost'],
        );
    }

    public function supplierPurchaseOrder(
        SupplierPurchaseOrder $supplierPurchaseOrder
    ): View {
        $this->safeLoad($supplierPurchaseOrder, [
            'supplier',
            'items.item',
            'purchaseRequest',
        ]);

        return $this->render(
            document: $supplierPurchaseOrder,
            title: 'أمر توريد',
            number: $supplierPurchaseOrder->document_number
                ?? $supplierPurchaseOrder->order_number
                ?? (string) $supplierPurchaseOrder->getKey(),
            date: $supplierPurchaseOrder->order_date
                ?? $supplierPurchaseOrder->date
                ?? $supplierPurchaseOrder->created_at,
            partyLabel: 'المورد',
            partyName: $supplierPurchaseOrder->supplier?->name ?? '—',
            meta: [
                'رقم طلب الشراء' =>
                    $supplierPurchaseOrder->purchaseRequest?->code
                    ?? $supplierPurchaseOrder->purchaseRequest?->document_number
                    ?? (filled($supplierPurchaseOrder->purchase_request_id)
                        ? (string) $supplierPurchaseOrder->purchase_request_id
                        : '—'),
                'تاريخ التسليم' =>
                    $this->formatDate(
                        $supplierPurchaseOrder->required_delivery_date
                        ?? $supplierPurchaseOrder->delivery_date
                    ),
                'مكان التسليم' =>
                    $supplierPurchaseOrder->delivery_location ?? '—',
                'الحالة' => $supplierPurchaseOrder->status ?? '—',
            ],
            notes: $supplierPurchaseOrder->notes,
            items: $supplierPurchaseOrder->items ?? collect(),
            quantityFields: ['ordered_quantity', 'quantity'],
            priceFields: ['unit_price', 'unit_cost'],
        );
    }

    public function supplierPaymentVoucher(
        SupplierPaymentVoucher $supplierPaymentVoucher
    ): View {
        $this->safeLoad($supplierPaymentVoucher, [
            'supplier',
            'treasury',
            'allocations.purchaseInvoice',
        ]);

        return view('print.purchasing-document', [
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
            'documentTitle' => 'سند صرف مورد',
            'documentNumber' =>
                $supplierPaymentVoucher->document_number
                ?? (string) $supplierPaymentVoucher->getKey(),
            'documentDate' => $this->formatDate(
                $supplierPaymentVoucher->date
                ?? $supplierPaymentVoucher->payment_date
                ?? $supplierPaymentVoucher->created_at
            ),
            'partyLabel' => 'المورد',
            'partyName' => $supplierPaymentVoucher->supplier?->name ?? '—',
            'meta' => [
                'الخزينة' =>
                    $supplierPaymentVoucher->treasury?->name ?? '—',
                'طريقة الدفع' =>
                    $this->valueLabel(
                        $supplierPaymentVoucher->payment_method
                    ),
                'الرقم المرجعي' =>
                    $supplierPaymentVoucher->reference_number ?? '—',
                'الحالة' =>
                    $supplierPaymentVoucher->status ?? '—',
            ],
            'items' => collect(),
            'notes' => $supplierPaymentVoucher->notes,
            'amount' => (float) ($supplierPaymentVoucher->amount ?? 0),
            'document' => $supplierPaymentVoucher,
            'quantityFields' => [],
            'priceFields' => [],
        ]);
    }

    public function purchaseInvoice(PurchaseInvoice $purchaseInvoice): View
    {
        $this->safeLoad($purchaseInvoice, [
            'supplier',
            'warehouse',
            'items.item',
            'supplierPurchaseOrder',
        ]);

        return $this->render(
            document: $purchaseInvoice,
            title: 'فاتورة شراء',
            number: $purchaseInvoice->document_number
                ?? $purchaseInvoice->invoice_number
                ?? (string) $purchaseInvoice->getKey(),
            date: $purchaseInvoice->invoice_date
                ?? $purchaseInvoice->date
                ?? $purchaseInvoice->created_at,
            partyLabel: 'المورد',
            partyName: $purchaseInvoice->supplier?->name ?? '—',
            meta: [
                'رقم فاتورة المورد' =>
                    $purchaseInvoice->supplier_invoice_number
                    ?? $purchaseInvoice->invoice_number
                    ?? '—',
                'المخزن' => $purchaseInvoice->warehouse?->name ?? '—',
                'رقم أمر توريد المورد' =>
                    $purchaseInvoice->supplierPurchaseOrder?->code
                    ?? $purchaseInvoice->supplierPurchaseOrder?->document_number
                    ?? (filled($purchaseInvoice->supplier_purchase_order_id)
                        ? (string) $purchaseInvoice->supplier_purchase_order_id
                        : '—'),
                'حالة السداد' => $purchaseInvoice->paymentStatusLabel(),
            ],
            notes: $purchaseInvoice->notes,
            items: $purchaseInvoice->items ?? collect(),
            quantityFields: ['quantity', 'received_quantity'],
            priceFields: ['unit_cost', 'unit_price'],
        );
    }

    private function render(
        Model $document,
        string $title,
        string $number,
        mixed $date,
        string $partyLabel,
        string $partyName,
        array $meta,
        mixed $notes,
        mixed $items,
        array $quantityFields,
        array $priceFields,
    ): View {
        return view('print.purchasing-document', [
            'settings' => CompanySetting::current(),
            'printedAt' => now(),
            'documentTitle' => $title,
            'documentNumber' => $number,
            'documentDate' => $this->formatDate($date),
            'partyLabel' => $partyLabel,
            'partyName' => $partyName,
            'meta' => $meta,
            'notes' => $notes,
            'items' => collect($items),
            'amount' => null,
            'document' => $document,
            'quantityFields' => $quantityFields,
            'priceFields' => $priceFields,
        ]);
    }

    private function safeLoad(Model $model, array $relations): void
    {
        foreach ($relations as $relation) {
            $root = explode('.', $relation)[0];

            if (method_exists($model, $root)) {
                $model->loadMissing($relation);
            }
        }
    }

    private function formatDate(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function valueLabel(mixed $value): string
    {
        if (blank($value)) {
            return '—';
        }

        if (is_object($value) && method_exists($value, 'label')) {
            return (string) $value->label();
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }
}
