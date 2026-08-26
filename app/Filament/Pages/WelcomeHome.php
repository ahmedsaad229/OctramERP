<?php

namespace App\Filament\Pages;

use App\Filament\Resources\CustomerPurchaseOrders\CustomerPurchaseOrderResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Items\ItemResource;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Filament\Resources\SalesQuotations\SalesQuotationResource;
use App\Filament\Resources\SupplierPurchaseOrders\SupplierPurchaseOrderResource;
use App\Filament\Resources\Suppliers\SupplierResource;
use App\Models\CompanySetting;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

class WelcomeHome extends BaseDashboard
{
    protected static ?string $navigationLabel = 'الرئيسية';

    protected static ?string $title = 'الرئيسية';

    protected static ?int $navigationSort = -100;

    protected string $view = 'filament.pages.welcome-home';

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function getHeading(): string|Htmlable
    {
        return '';
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $settings = CompanySetting::current();

        return [
            'user' => $user,
            'settings' => $settings,
            'quickLinks' => $this->quickLinks(),
            'todayLabel' => now()->locale('ar')->translatedFormat('l، j F Y'),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function quickLinks(): array
    {
        $links = [
            [
                'label' => 'لوحة المعلومات',
                'description' => 'مؤشرات الأداء والتحليلات',
                'icon' => '📊',
                'permission' => 'dashboard.view',
                'url' => fn (): string => CompanyDashboard::getUrl(),
                'tone' => 'blue',
            ],
            [
                'label' => 'عرض سعر جديد',
                'description' => 'إنشاء عرض سعر للعميل',
                'icon' => '📄',
                'permission' => 'sales_quotations.create',
                'url' => fn (): string => SalesQuotationResource::getUrl('create'),
                'tone' => 'indigo',
            ],
            [
                'label' => 'فاتورة بيع',
                'description' => 'إنشاء فاتورة بيع جديدة',
                'icon' => '🧾',
                'permission' => 'sales_invoices.create',
                'url' => fn (): string => SalesInvoiceResource::getUrl('create'),
                'tone' => 'green',
            ],
            [
                'label' => 'أمر توريد عميل',
                'description' => 'فتح أوامر توريد العملاء',
                'icon' => '📦',
                'permission' => 'customer_purchase_orders.view',
                'url' => fn (): string => CustomerPurchaseOrderResource::getUrl('index'),
                'tone' => 'cyan',
            ],
            [
                'label' => 'طلب شراء',
                'description' => 'إنشاء طلب شراء جديد',
                'icon' => '🛒',
                'permission' => 'purchase_requests.create',
                'url' => fn (): string => PurchaseRequestResource::getUrl('create'),
                'tone' => 'amber',
            ],
            [
                'label' => 'أوامر التوريد',
                'description' => 'متابعة أوامر توريد الموردين',
                'icon' => '🚚',
                'permission' => 'supplier_purchase_orders.view',
                'url' => fn (): string => SupplierPurchaseOrderResource::getUrl('index'),
                'tone' => 'orange',
            ],
            [
                'label' => 'فاتورة شراء',
                'description' => 'إنشاء فاتورة شراء جديدة',
                'icon' => '🧮',
                'permission' => 'purchase_invoices.create',
                'url' => fn (): string => PurchaseInvoiceResource::getUrl('create'),
                'tone' => 'violet',
            ],
            [
                'label' => 'الأصناف',
                'description' => 'البحث وإدارة الأصناف',
                'icon' => '🏷️',
                'permission' => 'items.view',
                'url' => fn (): string => ItemResource::getUrl('index'),
                'tone' => 'slate',
            ],
            [
                'label' => 'العملاء',
                'description' => 'قائمة العملاء وبياناتهم',
                'icon' => '👥',
                'permission' => 'customers.view',
                'url' => fn (): string => CustomerResource::getUrl('index'),
                'tone' => 'rose',
            ],
            [
                'label' => 'الموردون',
                'description' => 'قائمة الموردين وبياناتهم',
                'icon' => '🤝',
                'permission' => 'suppliers.view',
                'url' => fn (): string => SupplierResource::getUrl('index'),
                'tone' => 'teal',
            ],
        ];

        return collect($links)
            ->filter(fn (array $link): bool => $this->allowed($link['permission']))
            ->map(function (array $link): array {
                try {
                    $link['url'] = $link['url']();
                } catch (\Throwable) {
                    $link['url'] = null;
                }

                return $link;
            })
            ->filter(fn (array $link): bool => filled($link['url']))
            ->values()
            ->all();
    }

    private function allowed(string $permission): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if ((bool) ($user->is_admin ?? false)) {
            return true;
        }

        return $user->hasPermission($permission);
    }
}
