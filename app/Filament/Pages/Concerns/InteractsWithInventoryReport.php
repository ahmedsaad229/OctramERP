<?php

namespace App\Filament\Pages\Concerns;

trait InteractsWithInventoryReport
{
    /** @var array<string, mixed> */
    public array $data = [];

    /** @var array<string, mixed> */
    public array $report = [];

    abstract protected function reportKey(): string;

    /** @return array<string, mixed> */
    abstract protected function generateReport(array $filters): array;

    public function mount(): void
    {
        $this->form->fill();
        $this->refreshReport();
    }

    public function refreshReport(): void
    {
        $this->report = $this->generateReport($this->form->getState());
    }

    public function resetReport(): void
    {
        $this->form->fill();
        $this->resetValidation();
        $this->refreshReport();
    }

    public function printUrl(): string
    {
        return route('inventory-reports.print', [
            'report' => $this->reportKey(),
            ...array_filter($this->data, fn ($value): bool => filled($value)),
        ]);
    }

    public function excelUrl(): string
    {
        return route('inventory-reports.excel', [
            'report' => $this->reportKey(),
            ...array_filter($this->data, fn ($value): bool => filled($value)),
        ]);
    }
}
