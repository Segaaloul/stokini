<?php

namespace App\Message;

class OptimizeImageMessage
{
    private int $dossierId;
    private string $filename;

    public function __construct(int $dossierId, string $filename)
    {
        $this->dossierId = $dossierId;
        $this->filename = $filename;
    }

    public function getDossierId(): int
    {
        return $this->dossierId;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
