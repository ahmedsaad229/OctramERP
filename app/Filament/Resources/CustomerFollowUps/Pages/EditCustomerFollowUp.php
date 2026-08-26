<?php

namespace App\Filament\Resources\CustomerFollowUps\Pages;

use App\Filament\Actions\ProtectedDeleteAction;
use App\Filament\Resources\CustomerFollowUps\CustomerFollowUpResource;
use App\Models\CustomerFollowUp;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditCustomerFollowUp extends EditRecord
{
    protected static string $resource = CustomerFollowUpResource::class;

    protected static ?string $title = 'تعديل متابعة العميل';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('طباعة / حفظ PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(
                    fn (CustomerFollowUp $record): string => route(
                        'customer-follow-ups.print',
                        ['customerFollowUp' => $record]
                    )
                )
                ->openUrlInNewTab(),

            ProtectedDeleteAction::make()
                ->modalHeading('حذف متابعة العميل'),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث متابعة العميل بنجاح.';
    }
}
