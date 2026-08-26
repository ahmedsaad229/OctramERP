<?php

namespace App\Filament\Resources\PurchaseRequests\Pages;


use Filament\Actions\Action;
use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\PurchaseRequests\PurchaseRequestResource;
use App\Filament\Resources\PurchaseRequests\Schemas\PurchaseRequestForm;
use App\Models\PurchaseRequest;
use App\Services\PurchaseRequestService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditPurchaseRequest extends EditRecord
{
    protected static string $resource = PurchaseRequestResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data = [
            ...$data,
            ...PurchaseRequestForm::departmentFormState($data['department'] ?? null),
            'requester_name' => $this->getRecord()->requestedBy?->name,
        ];
        $data['items'] = $this->getRecord()
            ->items()
            ->with([
                'unit:id,name',
                'purchaseOrderItems.purchaseInvoiceItems',
            ])
            ->get()
            ->map(function ($item): array {
                $requested = (float) $item->requested_quantity;

                $ordered = (float) $item->purchaseOrderItems
                    ->sum('ordered_quantity');

                $invoiced = (float) $item->purchaseOrderItems
                    ->flatMap(fn ($orderItem) => $orderItem->purchaseInvoiceItems)
                    ->sum('quantity');

                $orderedStatus = $ordered <= 0
                    ? ['لم يتم', '#64748b']
                    : ($ordered + 0.00001 >= $requested
                        ? ['تم', '#15803d']
                        : ['جزئي', '#d97706']);

                $invoicedStatus = $invoiced <= 0
                    ? ['لم يتم', '#64748b']
                    : ($invoiced + 0.00001 >= $requested
                        ? ['تم', '#15803d']
                        : ['جزئي', '#d97706']);

                $requestedText = \App\Support\QuantityFormatter::formatForDisplay($requested);
                $orderedText = \App\Support\QuantityFormatter::formatForDisplay($ordered);
                $invoicedText = \App\Support\QuantityFormatter::formatForDisplay($invoiced);

                $followup = '
                    <div style="
                        display:grid;
                        grid-template-columns:repeat(2,minmax(0,1fr));
                        gap:10px;
                        width:100%;
                    ">
                        <div style="
                            padding:10px 12px;
                            border:1px solid #dbe4ee;
                            border-radius:8px;
                            background:#f8fafc;
                        ">
                            <div style="font-size:12px;color:#64748b;margin-bottom:4px">
                                أمر التوريد
                            </div>

                            <div style="font-weight:800;color:' . $orderedStatus[1] . '">
                                ' . $orderedStatus[0] . '
                            </div>

                            <div style="margin-top:3px;color:#475569">
                                ' . $orderedText . ' من ' . $requestedText . '
                            </div>
                        </div>

                        <div style="
                            padding:10px 12px;
                            border:1px solid #dbe4ee;
                            border-radius:8px;
                            background:#f8fafc;
                        ">
                            <div style="font-size:12px;color:#64748b;margin-bottom:4px">
                                فاتورة الشراء
                            </div>

                            <div style="font-weight:800;color:' . $invoicedStatus[1] . '">
                                ' . $invoicedStatus[0] . '
                            </div>

                            <div style="margin-top:3px;color:#475569">
                                ' . $invoicedText . ' من ' . $requestedText . '
                            </div>
                        </div>
                    </div>
                ';

                return [
                    'id' => $item->getKey(),

                    ...$item->only([
                        'item_id',
                        'unit_id',
                        'requested_quantity',
                        'notes',
                    ]),

                    'unit_name' => $item->unit?->name,

                    'purchase_followup_html' => $followup,
                ];
            })
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(PurchaseRequestService::class)->update($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
                        Action::make('print')
                ->label('طباعة / حفظ PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(
                    fn (PurchaseRequest $record): string => route(
                        'purchase-requests.print',
                        $record
                    )
                )
                ->openUrlInNewTab(),
ProtectedDeleteAction::make()
                ->using(fn (PurchaseRequest $record): bool => app(PurchaseRequestService::class)->delete($record)),
        ];
    }
}
