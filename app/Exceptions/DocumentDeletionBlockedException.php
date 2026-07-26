<?php

namespace App\Exceptions;

use Exception;

class DocumentDeletionBlockedException extends Exception
{
    public function __construct(
        private readonly string $notificationTitle,
        string $message,
        private readonly ?string $dependencyType = null,
        private readonly ?string $dependentDocumentNumber = null,
        private readonly ?int $linkedCount = null,
    ) {
        parent::__construct($message);
    }

    public function title(): string
    {
        return $this->notificationTitle;
    }

    public function dependencyType(): ?string
    {
        return $this->dependencyType;
    }

    public function dependentDocumentNumber(): ?string
    {
        return $this->dependentDocumentNumber;
    }

    public function linkedCount(): ?int
    {
        return $this->linkedCount;
    }

    public function report(): bool
    {
        return false;
    }
}
