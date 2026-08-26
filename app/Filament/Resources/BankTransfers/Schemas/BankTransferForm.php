<?php
namespace App\Filament\Resources\BankTransfers\Schemas;
use Filament\Forms\Components\DatePicker; use Filament\Forms\Components\Select; use Filament\Forms\Components\TextInput; use Filament\Forms\Components\Textarea; use Filament\Schemas\Components\Grid; use Filament\Schemas\Schema; use Illuminate\Database\Eloquent\Builder;
class BankTransferForm { public static function configure(Schema $schema):Schema{return $schema->components([Grid::make(2)->schema([
TextInput::make('document_number')->label('رقم التحويل')->placeholder('يولد تلقائيًا')->readOnly()->dehydrated(false), DatePicker::make('transfer_date')->label('تاريخ التحويل')->default(now())->native(false)->required(),
Select::make('from_bank_account_id')->label('من حساب بنكي')->relationship('fromAccount','account_name',modifyQueryUsing:fn(Builder $q)=>$q->with('bank')->where('is_active',true))->getOptionLabelFromRecordUsing(fn($r)=>$r->displayName())->searchable()->preload()->required()->live(),
Select::make('to_bank_account_id')->label('إلى حساب بنكي')->relationship('toAccount','account_name',modifyQueryUsing:fn(Builder $q)=>$q->with('bank')->where('is_active',true))->getOptionLabelFromRecordUsing(fn($r)=>$r->displayName())->searchable()->preload()->required()->different('from_bank_account_id'),
TextInput::make('amount')->label('قيمة التحويل')->numeric()->minValue(.01)->step(.01)->required(), TextInput::make('fees')->label('العمولة البنكية')->numeric()->minValue(0)->step(.01)->default(0), TextInput::make('reference_number')->label('الرقم المرجعي')->maxLength(255),
]),Textarea::make('notes')->label('البيان / الملاحظات')->rows(3)->columnSpanFull(),]);}}
