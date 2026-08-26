<?php
namespace App\Filament\Resources\Banks\Pages;
use App\Filament\Resources\Banks\BankResource; use Filament\Resources\Pages\EditRecord;
class EditBank extends EditRecord { protected static string $resource = BankResource::class; protected function getHeaderActions(): array { return [\Filament\Actions\DeleteAction::make()]; } }
