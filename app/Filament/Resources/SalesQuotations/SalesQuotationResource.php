<?php

namespace App\Filament\Resources\SalesQuotations;

use App\Filament\Resources\SalesQuotations\Pages\CreateSalesQuotation;
use App\Filament\Resources\SalesQuotations\Pages\EditSalesQuotation;
use App\Filament\Resources\SalesQuotations\Pages\ListSalesQuotations;
use App\Filament\Resources\SalesQuotations\Pages\ViewSalesQuotation;
use App\Filament\Resources\SalesQuotations\Schemas\SalesQuotationForm;
use App\Filament\Resources\SalesQuotations\Schemas\SalesQuotationInfolist;
use App\Filament\Resources\SalesQuotations\Tables\SalesQuotationsTable;
use App\Models\SalesQuotation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SalesQuotationResource extends Resource
{
    protected static ?string $model = SalesQuotation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'quotation_number';

    protected static ?string $navigationLabel = 'عروض الأسعار';

    protected static ?string $modelLabel = 'عرض سعر';

    protected static ?string $pluralModelLabel = 'عروض الأسعار';

    protected static string|\UnitEnum|null $navigationGroup = 'المبيعات';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return SalesQuotationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalesQuotationsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SalesQuotationInfolist::configure($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalesQuotations::route('/'),
            'create' => CreateSalesQuotation::route('/create'),
            'view' => ViewSalesQuotation::route('/{record}'),
            'edit' => EditSalesQuotation::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'customer',
            'warehouse',
            'creator',
            'items.item',
            'items.unit',
            'items.salesInvoiceItems',
            'salesInvoices',
        ]);
    }
}
