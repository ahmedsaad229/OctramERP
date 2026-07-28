<?php

namespace App\Http\Controllers;

use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Models\CompanySetting;
use App\Models\CustomerPurchaseOrder;
use App\Services\CustomerPurchaseOrderService;
use Illuminate\Contracts\View\View;

class CustomerPurchaseOrderPrintController extends Controller
{
    public function __invoke(CustomerPurchaseOrder $customerPurchaseOrder): View
    {
        abort_unless(CustomerPurchaseOrderResource::canEdit($customerPurchaseOrder), 403);

        return view('print.customer-purchase-order', [
            'order' => $customerPurchaseOrder->load(['customer', 'items.item', 'items.unit', 'attachments']),
            'executionDocuments' => app(CustomerPurchaseOrderService::class)->linkedDocuments($customerPurchaseOrder),
            'settings' => CompanySetting::current(),
        ]);
    }
}
