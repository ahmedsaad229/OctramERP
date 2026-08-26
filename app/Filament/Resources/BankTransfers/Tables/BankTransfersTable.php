<?php
namespace App\Filament\Resources\BankTransfers\Tables;
use Filament\Actions\EditAction; use Filament\Tables\Columns\TextColumn; use Filament\Tables\Table;
class BankTransfersTable { public static function configure(Table $table):Table{return $table->columns([
TextColumn::make('document_number')->label('رقم التحويل')->searchable()->sortable(), TextColumn::make('transfer_date')->label('التاريخ')->date('Y/m/d')->sortable(), TextColumn::make('fromAccount.bank.name')->label('من بنك'), TextColumn::make('fromAccount.account_name')->label('من حساب'), TextColumn::make('toAccount.bank.name')->label('إلى بنك'), TextColumn::make('toAccount.account_name')->label('إلى حساب'), TextColumn::make('amount')->label('المبلغ')->money('EGP')->sortable(), TextColumn::make('fees')->label('العمولة')->money('EGP'),
])->defaultSort('transfer_date','desc')->recordActions([EditAction::make()]);}}
