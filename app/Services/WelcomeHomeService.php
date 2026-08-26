<?php

namespace App\Services;

use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use App\Filament\Resources\SupplierPurchaseOrders\SupplierPurchaseOrderResource;
use App\Models\CustomerPurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\SalesQuotation;
use App\Models\SupplierPurchaseOrder;
use App\Models\User;
use Illuminate\Support\Collection;

class WelcomeHomeService
{
    /** @return array<string, mixed> */
    public function forUser(?User $user): array
    {
        if (! $user) {
            return [
                'tasks' => [],
                'recent' => [],
            ];
        }

        return [
            'tasks' => $this->tasks($user),
            'recent' => $this->recentWork($user),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function tasks(User $user): array
    {
        $tasks = [];

        if (SalesQuotationResource::canViewAny()) {
            $todayQuotations = SalesQuotation::query()
                ->where(function ($query) use ($user): void {
                    $query
                        ->where('created_by', $user->getKey())
                        ->orWhere('sales_responsible_id', $user->getKey());
                })
                ->whereDate('quotation_date', today())
                ->count();

            $expiringQuotations = SalesQuotation::query()
                ->where('sales_responsible_id', $user->getKey())
                ->whereNotNull('valid_until')
                ->whereBetween('valid_until', [
                    today()->toDateString(),
                    today()->copy()->addDays(3)->toDateString(),
                ])
                ->get()
                ->filter(fn (SalesQuotation $quotation): bool => ! $quotation->isFullyConverted())
                ->count();

            if ($todayQuotations > 0) {
                $tasks[] = [
                    'label' => 'عروض سعر اليوم',
                    'value' => $todayQuotations,
                    'unit' => 'عرض',
                    'icon' => '📄',
                    'tone' => 'blue',
                    'url' => $this->resourceUrl(SalesQuotationResource::class),
                ];
            }

            if ($expiringQuotations > 0) {
                $tasks[] = [
                    'label' => 'عروض تنتهي قريبًا',
                    'value' => $expiringQuotations,
                    'unit' => 'عرض',
                    'icon' => '⏳',
                    'tone' => 'amber',
                    'url' => $this->resourceUrl(SalesQuotationResource::class),
                ];
            }
        }

        if (CustomerPurchaseOrderResource::canViewAny()) {
            $openCustomerOrders = CustomerPurchaseOrder::query()
                ->whereHas(
                    'salesQuotation',
                    fn ($query) => $query->where('sales_responsible_id', $user->getKey()),
                )
                ->whereNotIn('status', [
                    CustomerPurchaseOrder::STATUS_COMPLETED,
                    CustomerPurchaseOrder::STATUS_CANCELLED,
                ])
                ->count();

            if ($openCustomerOrders > 0) {
                $tasks[] = [
                    'label' => 'أوامر عملاء مفتوحة',
                    'value' => $openCustomerOrders,
                    'unit' => 'أمر',
                    'icon' => '📦',
                    'tone' => 'green',
                    'url' => $this->resourceUrl(CustomerPurchaseOrderResource::class),
                ];
            }
        }

        if (PurchaseRequestResource::canViewAny()) {
            $openPurchaseRequests = PurchaseRequest::query()
                ->where(function ($query) use ($user): void {
                    $query
                        ->where('created_by', $user->getKey())
                        ->orWhere('requested_by', $user->getKey());
                })
                ->with('items')
                ->get()
                ->filter(fn (PurchaseRequest $request): bool => $request->remainingQuantity() > 0.009)
                ->count();

            if ($openPurchaseRequests > 0) {
                $tasks[] = [
                    'label' => 'طلبات شراء مفتوحة',
                    'value' => $openPurchaseRequests,
                    'unit' => 'طلب',
                    'icon' => '🛒',
                    'tone' => 'violet',
                    'url' => $this->resourceUrl(PurchaseRequestResource::class),
                ];
            }
        }

        if (SupplierPurchaseOrderResource::canViewAny()) {
            $openSupplierOrders = SupplierPurchaseOrder::query()
                ->where('created_by', $user->getKey())
                ->with(['items', 'purchaseInvoiceItems'])
                ->get()
                ->filter(fn (SupplierPurchaseOrder $order): bool => $order->remainingToInvoiceQuantity() > 0.009)
                ->count();

            if ($openSupplierOrders > 0) {
                $tasks[] = [
                    'label' => 'أوامر توريد مفتوحة',
                    'value' => $openSupplierOrders,
                    'unit' => 'أمر',
                    'icon' => '🚚',
                    'tone' => 'orange',
                    'url' => $this->resourceUrl(SupplierPurchaseOrderResource::class),
                ];
            }
        }

        return array_slice($tasks, 0, 4);
    }

    /** @return array<int, array<string, mixed>> */
    private function recentWork(User $user): array
    {
        $recent = collect();

        if (SalesQuotationResource::canViewAny()) {
            $rows = SalesQuotation::query()
                ->with('customer:id,name')
                ->where(function ($query) use ($user): void {
                    $query
                        ->where('created_by', $user->getKey())
                        ->orWhere('sales_responsible_id', $user->getKey());
                })
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (SalesQuotation $quotation): array => [
                    'sort' => $quotation->updated_at?->timestamp ?? 0,
                    'type' => 'عرض سعر',
                    'icon' => '📄',
                    'number' => $quotation->quotation_number,
                    'party' => $quotation->customer?->name ?? '—',
                    'time' => $quotation->updated_at?->diffForHumans() ?? '—',
                    'url' => $this->recordUrl(SalesQuotationResource::class, $quotation),
                ]);

            $recent = $recent->concat($rows);
        }

        if (PurchaseRequestResource::canViewAny()) {
            $rows = PurchaseRequest::query()
                ->where(function ($query) use ($user): void {
                    $query
                        ->where('created_by', $user->getKey())
                        ->orWhere('requested_by', $user->getKey());
                })
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (PurchaseRequest $request): array => [
                    'sort' => $request->updated_at?->timestamp ?? 0,
                    'type' => 'طلب شراء',
                    'icon' => '🛒',
                    'number' => $request->code,
                    'party' => $request->purpose ?: 'طلب شراء',
                    'time' => $request->updated_at?->diffForHumans() ?? '—',
                    'url' => $this->recordUrl(PurchaseRequestResource::class, $request),
                ]);

            $recent = $recent->concat($rows);
        }

        if (SupplierPurchaseOrderResource::canViewAny()) {
            $rows = SupplierPurchaseOrder::query()
                ->with('supplier:id,name')
                ->where('created_by', $user->getKey())
                ->latest('updated_at')
                ->limit(5)
                ->get()
                ->map(fn (SupplierPurchaseOrder $order): array => [
                    'sort' => $order->updated_at?->timestamp ?? 0,
                    'type' => 'أمر توريد',
                    'icon' => '🚚',
                    'number' => $order->code,
                    'party' => $order->supplier?->name ?? '—',
                    'time' => $order->updated_at?->diffForHumans() ?? '—',
                    'url' => $this->recordUrl(SupplierPurchaseOrderResource::class, $order),
                ]);

            $recent = $recent->concat($rows);
        }

        return $recent
            ->sortByDesc('sort')
            ->take(5)
            ->values()
            ->all();
    }

    private function resourceUrl(string $resource): ?string
    {
        try {
            return $resource::canViewAny()
                ? $resource::getUrl('index')
                : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function recordUrl(string $resource, mixed $record): ?string
    {
        try {
            if ($resource::canEdit($record)) {
                return $resource::getUrl('edit', ['record' => $record]);
            }

            if ($resource::canView($record)) {
                return $resource::getUrl('view', ['record' => $record]);
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
