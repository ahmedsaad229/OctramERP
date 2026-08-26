<?php
namespace App\Filament\Resources\BankTransfers\Pages; use App\Filament\Resources\BankTransfers\BankTransferResource; use Filament\Actions\CreateAction; use Filament\Resources\Pages\ListRecords; class ListBankTransfers extends ListRecords { protected static string $resource=BankTransferResource::class; protected function getHeaderActions():array{return[CreateAction::make()];}}
