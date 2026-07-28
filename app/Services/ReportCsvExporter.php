<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportCsvExporter
{
    /** @param iterable<int, array<int, mixed>> $rows */
    public function download(string $filename, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'wb');
            fwrite($output, "\xEF\xBB\xBF");

            foreach ($rows as $row) {
                fputcsv($output, array_map([$this, 'safeValue'], $row));
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function safeValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
            return "'{$value}";
        }

        return $value;
    }
}
