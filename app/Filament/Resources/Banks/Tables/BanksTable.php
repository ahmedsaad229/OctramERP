<?php
namespace App\Filament\Resources\Banks\Tables;
use Filament\Actions\BulkActionGroup; use Filament\Actions\DeleteBulkAction; use Filament\Actions\EditAction; use Filament\Tables\Columns\IconColumn; use Filament\Tables\Columns\TextColumn; use Filament\Tables\Table;
class BanksTable { public static function configure(Table $table): Table { return $table->columns([
    TextColumn::make('code')->label('الكود')->searchable()->sortable(),
    TextColumn::make('name')->label('اسم البنك')->searchable()->sortable(),
    TextColumn::make('swift_code')->label('SWIFT')->placeholder('—'),
    TextColumn::make('accounts_count')->counts('accounts')->label('عدد الحسابات')->badge(),
    IconColumn::make('is_active')->label('نشط')->boolean(),
])->recordActions([EditAction::make()])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]); } }
