<?php

namespace App\Filament\Resources\DueObligations\Tables;

use App\Enums\PaymentType;
use App\Filament\Resources\PurchaseInvoices\PurchaseInvoiceResource;
use App\Filament\Resources\SalesInvoices\SalesInvoiceResource;
use App\Models\Customer;
use App\Models\DueObligation;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Support\ArabicMoney;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class DueObligationsTable
{
    public static function configure(Table $table): Table
    {
        $today = now()->toDateString();

        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->orderByRaw(
                    'CASE
                        WHEN payment_type = ? AND due_date < ? THEN 1
                        WHEN payment_type = ? AND due_date = ? THEN 2
                        WHEN payment_type = ? AND due_date > ? THEN 3
                        ELSE 4
                    END',
                    ['credit', $today, 'credit', $today, 'credit', $today],
                )
                ->orderByRaw(
                    'CASE WHEN payment_type = ? AND due_date > ? THEN due_date END ASC',
                    ['credit', $today],
                )
                ->orderBy('invoice_date')
                ->orderBy('source_id'))
            ->columns([
                TextColumn::make('source_type')
                    ->label('النوع')
                    ->formatStateUsing(fn (string $state): string => $state === DueObligation::TYPE_SALE ? 'بيع' : 'شراء')
                    ->badge()
                    ->color(fn (string $state): string => $state === DueObligation::TYPE_SALE ? 'success' : 'info')
                    ->alignCenter(),
                TextColumn::make('document_number')
                    ->label('رقم المستند')
                    ->searchable()
                    ->alignRight(),
                TextColumn::make('party_name')
                    ->label('العميل / المورد')
                    ->searchable()
                    ->wrap()
                    ->width('18rem')
                    ->alignRight(),
                TextColumn::make('invoice_date')
                    ->label('التاريخ')
                    ->date('Y/m/d')
                    ->toggleable()
                    ->alignCenter(),
                TextColumn::make('total_amount')
                    ->label('قيمة الفاتورة')
                    ->formatStateUsing(fn (mixed $state): string => ArabicMoney::format($state))
                    ->alignRight(),
                TextColumn::make('payment_type')
                    ->label('نوع التعامل')
                    ->formatStateUsing(fn (string $state): string => PaymentType::from($state)->label())
                    ->badge()
                    ->color(fn (string $state): string => $state === PaymentType::Cash->value ? 'gray' : 'warning')
                    ->toggleable()
                    ->alignCenter(),
                TextColumn::make('due_date')
                    ->label('تاريخ الاستحقاق')
                    ->date('Y/m/d')
                    ->placeholder(self::mutedDash())
                    ->alignCenter(),
                TextColumn::make('days')
                    ->label('عدد الأيام')
                    ->state(fn (DueObligation $record): string => self::daysLabel($record))
                    ->color(fn (DueObligation $record): ?string => $record->payment_type === PaymentType::Cash->value ? 'gray' : null)
                    ->toggleable()
                    ->alignCenter(),
                TextColumn::make('due_status')
                    ->label('حالة الاستحقاق')
                    ->state(fn (DueObligation $record): string => self::statusLabel($record))
                    ->badge()
                    ->color(fn (DueObligation $record): string => self::statusColor($record))
                    ->alignCenter(),
            ])
            ->filters(self::filters())
            ->recordClasses(fn (DueObligation $record): string => self::rowClass($record))
            ->emptyStateHeading('لا توجد استحقاقات')
            ->emptyStateDescription('لا توجد فواتير مطابقة للبحث أو عوامل التصفية الحالية.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->recordActions([
                Action::make('view_invoice')
                    ->label('عرض الفاتورة')
                    ->icon('heroicon-o-eye')
                    ->url(fn (DueObligation $record): string => $record->source_type === DueObligation::TYPE_SALE
                        ? SalesInvoiceResource::getUrl('view', ['record' => $record->source_id])
                        : PurchaseInvoiceResource::getUrl('view', ['record' => $record->source_id])),
            ])
            ->recordActionsColumnLabel('عرض الفاتورة')
            ->recordActionsAlignment('center');
    }

    private static function daysLabel(DueObligation $record): string
    {
        if ($record->payment_type === PaymentType::Cash->value || ! $record->due_date) {
            return '—';
        }

        if ($record->due_date->isToday()) {
            return 'اليوم';
        }

        $days = abs((int) $record->due_date->copy()->startOfDay()->diffInDays(now()->startOfDay()));

        return $record->due_date->isFuture()
            ? 'متبقي '.self::dayCount($days)
            : 'متأخر '.self::dayCount($days);
    }

    private static function dayCount(int $days): string
    {
        if ($days === 1) {
            return 'يوم واحد';
        }

        if ($days === 2) {
            return 'يومان';
        }

        if ($days >= 3 && $days <= 10) {
            return "{$days} أيام";
        }

        return "{$days} يوم";
    }

    private static function rowClass(DueObligation $record): string
    {
        return match (self::status($record)) {
            DueObligation::STATUS_OVERDUE => 'due-obligation-row-overdue',
            DueObligation::STATUS_TODAY => 'due-obligation-row-today',
            DueObligation::STATUS_CASH => 'due-obligation-row-cash',
            default => '',
        };
    }

    private static function mutedDash(): HtmlString
    {
        return new HtmlString('<span style="color: rgb(156 163 175)">—</span>');
    }

    private static function status(DueObligation $record): string
    {
        if ($record->payment_type === PaymentType::Cash->value || ! $record->due_date) {
            return DueObligation::STATUS_CASH;
        }

        if ($record->due_date->isToday()) {
            return DueObligation::STATUS_TODAY;
        }

        return $record->due_date->isPast()
            ? DueObligation::STATUS_OVERDUE
            : DueObligation::STATUS_FUTURE;
    }

    private static function statusLabel(DueObligation $record): string
    {
        return match (self::status($record)) {
            DueObligation::STATUS_FUTURE => 'مستحق لاحقاً',
            DueObligation::STATUS_TODAY => 'مستحق اليوم',
            DueObligation::STATUS_OVERDUE => 'متأخر',
            default => 'كاش',
        };
    }

    private static function statusColor(DueObligation $record): string
    {
        return match (self::status($record)) {
            DueObligation::STATUS_FUTURE => 'info',
            DueObligation::STATUS_TODAY => 'warning',
            DueObligation::STATUS_OVERDUE => 'danger',
            default => 'gray',
        };
    }

    /**
     * @return array<int, Filter|SelectFilter>
     */
    private static function filters(): array
    {
        return [
            SelectFilter::make('source_type')
                ->label('النوع')
                ->options([
                    DueObligation::TYPE_SALE => 'بيع',
                    DueObligation::TYPE_PURCHASE => 'شراء',
                ]),
            SelectFilter::make('payment_type')
                ->label('نوع التعامل')
                ->options(PaymentType::options()),
            SelectFilter::make('party')
                ->label('العميل / المورد')
                ->searchable()
                ->options(fn (): array => [
                    'العملاء' => Customer::query()->orderBy('name')->pluck('name', 'id')
                        ->mapWithKeys(fn (string $name, int|string $id): array => ["sale:{$id}" => $name])
                        ->all(),
                    'الموردون' => Supplier::query()->orderBy('name')->pluck('name', 'id')
                        ->mapWithKeys(fn (string $name, int|string $id): array => ["purchase:{$id}" => $name])
                        ->all(),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    if (blank($data['value'] ?? null)) {
                        return $query;
                    }

                    [$type, $id] = explode(':', $data['value'], 2);

                    return $query->where('source_type', $type)->where('party_id', $id);
                }),
            SelectFilter::make('due_status')
                ->label('حالة الاستحقاق')
                ->options([
                    DueObligation::STATUS_TODAY => 'اليوم',
                    DueObligation::STATUS_FUTURE => 'لاحقًا',
                    DueObligation::STATUS_OVERDUE => 'متأخر',
                ])
                ->query(fn (Builder $query, array $data): Builder => self::applyStatusFilter($query, $data['value'] ?? null)),
            Filter::make('overdue')
                ->label('المتأخرة فقط')
                ->query(fn (Builder $query): Builder => self::applyStatusFilter($query, DueObligation::STATUS_OVERDUE)),
            Filter::make('due_date')
                ->label('نطاق تاريخ الاستحقاق')
                ->schema([
                    DatePicker::make('from')->label('من'),
                    DatePicker::make('until')->label('إلى'),
                ])
                ->query(fn (Builder $query, array $data): Builder => $query
                    ->when($data['from'] ?? null, fn (Builder $query, mixed $date): Builder => $query->whereDate('due_date', '>=', $date))
                    ->when($data['until'] ?? null, fn (Builder $query, mixed $date): Builder => $query->whereDate('due_date', '<=', $date))),
            SelectFilter::make('warehouse_id')
                ->label('المخزن')
                ->options(fn (): array => Warehouse::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
        ];
    }

    private static function applyStatusFilter(Builder $query, ?string $status): Builder
    {
        $today = now()->toDateString();

        return match ($status) {
            DueObligation::STATUS_TODAY => $query->where('payment_type', 'credit')->whereDate('due_date', $today),
            DueObligation::STATUS_FUTURE => $query->where('payment_type', 'credit')->whereDate('due_date', '>', $today),
            DueObligation::STATUS_OVERDUE => $query->where('payment_type', 'credit')->whereDate('due_date', '<', $today),
            default => $query,
        };
    }
}
