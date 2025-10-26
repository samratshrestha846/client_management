<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ClientExportDTO
{
    public ?bool $duplicatesOnly;

    public function __construct(?bool $duplicatesOnly = false)
    {
        $this->duplicatesOnly = $duplicatesOnly;
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            duplicatesOnly: $request->boolean('duplicates_only')
        );
    }
}
