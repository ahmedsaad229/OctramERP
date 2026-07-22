<?php

namespace App\Filament\Resources\OpeningStockVouchers\RelationManagers;

use App\Models\Item;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Form;

class OpeningStockItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'أصناف المستند';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('item_id')
                    ->label('الصنف')
                    ->relationship('item', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),

                Forms\Components\TextInput::make('quantity')
                    ->label('الكمية')
                    ->numeric()
                    ->required()
                    ->live(),

                Forms\Components\TextInput::make('unit_cost')
                    ->label('تكلفة الوحدة')
                    ->numeric()
                    ->required()
                    ->live(),

                Forms\Components\TextInput::make('total_cost')
                    ->label('الإجمالي')
                    ->numeric()
                    ->disabled()
                    ->dehydrated()
                    ->afterStateHydrated(function ($set, $get) {
                        $set(
                            'total_cost',
                            (float) $get('quantity') * (float) $get('unit_cost')
                        );
                    })
                    ->afterStateUpdated(function ($set, $get) {
                        $set(
                            'total_cost',
                            (float) $get('quantity') * (float) $get('unit_cost')
                        );
                    }),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('item.name')
            ->columns([
                Tables\Columns\TextColumn::make('item.code')
                    ->label('الكود'),

                Tables\Columns\TextColumn::make('item.name')
                    ->label('الصنف')
                    ->searchable(),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('الكمية')
                    ->numeric(2),

                Tables\Columns\TextColumn::make('unit_cost')
                    ->label('تكلفة الوحدة')
                    ->money('EGP'),

                Tables\Columns\TextColumn::make('total_cost')
                    ->label('الإجمالي')
                    ->money('EGP'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة صنف'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}