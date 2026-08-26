<?php

namespace App\Filament\Resources\CustomerPurchaseOrders\Tables;


use Filament\Actions\Action;
use App\Models\CustomerPurchaseOrder;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerPurchaseOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('id', 'desc')->columns([
            TextColumn::make('document_number')->label('رقم المستند')->searchable()->sortable(),
            TextColumn::make('customer_order_number')->label('رقم أمر العميل')->searchable(),
            TextColumn::make('order_date')->label('تاريخ الأمر')->date('d/m/Y')->sortable(),
            TextColumn::make('customer.name')->label('العميل')->searchable()->sortable(),
            TextColumn::make('project_name')->label('المشروع')->searchable(),
            TextColumn::make('required_delivery_date')->label('تاريخ التسليم')->date('d/m/Y'),
            TextColumn::make('status')->label('الحالة')->formatStateUsing(fn ($state) => CustomerPurchaseOrder::statusOptions()[$state] ?? '—')->badge(),
            TextColumn::make('execution_percentage')->label('نسبة التنفيذ')->suffix('%')->alignCenter(),
            TextColumn::make('remaining')->label('المتبقي')->state(fn (CustomerPurchaseOrder $record) => (float) $record->items()->sum('remaining_quantity'))->alignCenter(),
            TextColumn::make('delayed')->label('متأخر')->state(fn (CustomerPurchaseOrder $record) => $record->isDelayed() ? 'متأخر' : '—')->badge(),
        ])->filters([
            SelectFilter::make('customer_id')->label('العميل')->relationship('customer', 'name')->searchable()->preload(),
            SelectFilter::make('status')->label('الحالة')->options(CustomerPurchaseOrder::statusOptions()),
                ])->recordActions([
            Action::make('print')
                ->label('طباعة')
                ->icon('heroicon-o-printer')
                ->iconButton()
                ->color('gray')
                ->tooltip('طباعة أمر توريد العميل')
                ->url(
                    fn (CustomerPurchaseOrder $record): string => route(
                        'customer-purchase-orders.print',
                        $record
                    )
                )
                ->openUrlInNewTab(),

            ViewAction::make()
                ->iconButton()
                ->tooltip('عرض'),

            EditAction::make()
                ->iconButton()
                ->tooltip('تعديل'),
        ]);
    }
}
