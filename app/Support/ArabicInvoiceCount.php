<?php

namespace App\Support;

class ArabicInvoiceCount
{
    public static function credit(int $count): string
    {
        return match (true) {
            $count === 0 => 'لا توجد فواتير آجلة',
            $count === 1 => 'فاتورة آجلة واحدة',
            $count === 2 => 'فاتورتان آجلتان',
            $count <= 10 => "{$count} فواتير آجلة",
            default => "{$count} فاتورة آجلة",
        };
    }

    public static function dueToday(int $count): string
    {
        return match (true) {
            $count === 0 => 'لا توجد فواتير مستحقة اليوم',
            $count === 1 => 'فاتورة واحدة مستحقة اليوم',
            $count === 2 => 'فاتورتان مستحقتان اليوم',
            $count <= 10 => "{$count} فواتير مستحقة اليوم",
            default => "{$count} فاتورة مستحقة اليوم",
        };
    }

    public static function overdue(int $count): string
    {
        return match (true) {
            $count === 0 => 'لا توجد فواتير متأخرة',
            $count === 1 => 'فاتورة متأخرة واحدة',
            $count === 2 => 'فاتورتان متأخرتان',
            $count <= 10 => "{$count} فواتير متأخرة",
            default => "{$count} فاتورة متأخرة",
        };
    }
}
