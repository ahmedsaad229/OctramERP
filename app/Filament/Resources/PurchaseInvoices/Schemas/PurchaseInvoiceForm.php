<?php

namespace App\Filament\Resources\PurchaseInvoices\Schemas;

use App\Filament\Resources\OpeningStockVouchers\Schemas\OpeningStockVoucherForm;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PurchaseInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return OpeningStockVoucherForm::configure(
            $schema,
            [
                Select::make('supplier_id')->label('المورد')->relationship('supplier', 'name')->searchable()->preload()->required(),
                TextInput::make('invoice_number')->label('رقم الفاتورة')->required()->maxLength(255),
            ],
            'invoice_date',
        );
    }
}
