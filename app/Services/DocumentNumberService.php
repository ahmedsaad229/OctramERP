<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DocumentNumberService
{
    public const PURCHASE_INVOICE = 'purchase_invoice';
    public const SALES_INVOICE = 'sales_invoice';
    public const GOODS_RECEIPT = 'goods_receipt';
    public const GOODS_ISSUE = 'goods_issue';
    public const OPENING_STOCK = 'opening_stock';
    public const PAYMENT_VOUCHER = 'payment_voucher';
    public const RECEIPT_VOUCHER = 'receipt_voucher';
    public const TREASURY = 'treasury';

    private const PREFIXES = [
        self::PURCHASE_INVOICE => 'PUR',
        self::SALES_INVOICE => 'SAL',
        self::GOODS_RECEIPT => 'GRN',
        self::GOODS_ISSUE => 'GIS',
        self::OPENING_STOCK => 'OPN',
        self::PAYMENT_VOUCHER => 'PAY',
        self::RECEIPT_VOUCHER => 'REC',
        self::TREASURY => 'TRE',
    ];

    public function generate(string $documentType, ?Carbon $date = null): string
    {
        $prefix = $this->resolvePrefix($documentType);

        return DB::transaction(function () use ($prefix): string {
            DB::table('document_number_counters')->insertOrIgnore([
                'document_type' => $prefix,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $counter = DB::table('document_number_counters')
                ->where('document_type', $prefix)
                ->lockForUpdate()
                ->first();

            $nextNumber = $counter->last_number + 1;

            DB::table('document_number_counters')
                ->where('document_type', $prefix)
                ->update([
                    'last_number' => $nextNumber,
                    'updated_at' => now(),
                ]);

            return $prefix . '-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
        });
    }

    private function resolvePrefix(string $documentType): string
    {
        $normalizedType = strtolower(trim($documentType));

        if (isset(self::PREFIXES[$normalizedType])) {
            return self::PREFIXES[$normalizedType];
        }

        $prefix = strtoupper(trim($documentType));

        if (in_array($prefix, self::PREFIXES, true)) {
            return $prefix;
        }

        throw new InvalidArgumentException("Unsupported document type: {$documentType}");
    }
}
