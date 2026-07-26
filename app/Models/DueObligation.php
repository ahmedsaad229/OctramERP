<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class DueObligation extends Model
{
    public const TYPE_SALE = 'sale';

    public const TYPE_PURCHASE = 'purchase';

    public const STATUS_CASH = 'cash';

    public const STATUS_FUTURE = 'future';

    public const STATUS_TODAY = 'today';

    public const STATUS_OVERDUE = 'overdue';

    public $timestamps = false;

    protected $primaryKey = 'source_id';

    protected $guarded = [];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function getKey(): mixed
    {
        return "{$this->source_type}:{$this->source_id}";
    }

    public static function unifiedQuery(): QueryBuilder
    {
        $sales = DB::table('sales_invoices as invoices')
            ->join('customers as parties', 'parties.id', '=', 'invoices.customer_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'invoices.warehouse_id')
            ->select([
                'invoices.id as source_id',
                DB::raw("'sale' as source_type"),
                'invoices.document_number',
                'invoices.invoice_date',
                'invoices.customer_id as party_id',
                'parties.name as party_name',
                'invoices.payment_type',
                'invoices.due_date',
                'invoices.warehouse_id',
                'warehouses.name as warehouse_name',
                DB::raw('(
                    CASE
                        WHEN (
                            SELECT COALESCE(SUM(line_total), 0)
                            FROM sales_invoice_items
                            WHERE sales_invoice_id = invoices.id
                        ) - COALESCE(invoices.discount_amount, 0) > 0
                        THEN (
                            SELECT COALESCE(SUM(line_total), 0)
                            FROM sales_invoice_items
                            WHERE sales_invoice_id = invoices.id
                        ) - COALESCE(invoices.discount_amount, 0)
                        ELSE 0
                    END
                ) + COALESCE(invoices.tax_amount, 0) as total_amount'),
            ]);

        $purchases = DB::table('purchase_invoices as invoices')
            ->join('suppliers as parties', 'parties.id', '=', 'invoices.supplier_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'invoices.warehouse_id')
            ->select([
                'invoices.id as source_id',
                DB::raw("'purchase' as source_type"),
                'invoices.invoice_number as document_number',
                'invoices.invoice_date',
                'invoices.supplier_id as party_id',
                'parties.name as party_name',
                'invoices.payment_type',
                'invoices.due_date',
                'invoices.warehouse_id',
                'warehouses.name as warehouse_name',
                DB::raw('(
                    CASE
                        WHEN (
                            SELECT COALESCE(SUM(quantity * unit_cost), 0)
                            FROM purchase_invoice_items
                            WHERE purchase_invoice_id = invoices.id
                        ) - COALESCE(invoices.discount_amount, 0) > 0
                        THEN (
                            SELECT COALESCE(SUM(quantity * unit_cost), 0)
                            FROM purchase_invoice_items
                            WHERE purchase_invoice_id = invoices.id
                        ) - COALESCE(invoices.discount_amount, 0)
                        ELSE 0
                    END
                ) + COALESCE(invoices.tax_amount, 0) as total_amount'),
            ]);

        return $sales->unionAll($purchases);
    }

    public static function queryUnified(): Builder
    {
        return (new static)->newQuery()->fromSub(static::unifiedQuery(), 'due_obligations');
    }
}
