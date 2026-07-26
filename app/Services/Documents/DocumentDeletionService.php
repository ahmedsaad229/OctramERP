<?php

namespace App\Services\Documents;

use App\Models\GoodsIssueVoucher;
use App\Models\GoodsReceiptVoucher;
use App\Models\OpeningStockVoucher;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseRequest;
use App\Models\ReceiptVoucher;
use App\Models\SalesInvoice;
use App\Models\SupplierPaymentVoucher;
use App\Models\SupplierPurchaseOrder;
use App\Services\Inventory\GoodsIssueService;
use App\Services\Inventory\GoodsReceiptService;
use App\Services\Inventory\OpeningStockService;
use App\Services\Inventory\PurchaseInvoiceService;
use App\Services\Inventory\SalesInvoiceService;
use App\Services\PurchaseRequestService;
use App\Services\ReceiptVoucherService;
use App\Services\SupplierPaymentVoucherService;
use App\Services\SupplierPurchaseOrderService;
use Illuminate\Database\Eloquent\Model;

class DocumentDeletionService
{
    public function __construct(private readonly DocumentDeletionGuard $guard) {}

    public function delete(Model $document): bool
    {
        $this->guard->assertCanDelete($document);

        return match (true) {
            $document instanceof PurchaseRequest => app(PurchaseRequestService::class)->delete($document),
            $document instanceof SupplierPurchaseOrder => app(SupplierPurchaseOrderService::class)->delete($document),
            $document instanceof PurchaseInvoice => app(PurchaseInvoiceService::class)->delete($document),
            $document instanceof SalesInvoice => app(SalesInvoiceService::class)->delete($document),
            $document instanceof ReceiptVoucher => app(ReceiptVoucherService::class)->delete($document),
            $document instanceof SupplierPaymentVoucher => app(SupplierPaymentVoucherService::class)->delete($document),
            $document instanceof OpeningStockVoucher => app(OpeningStockService::class)->delete($document),
            $document instanceof GoodsReceiptVoucher => app(GoodsReceiptService::class)->delete($document),
            $document instanceof GoodsIssueVoucher => app(GoodsIssueService::class)->delete($document),
            default => (bool) $document->delete(),
        };
    }
}
