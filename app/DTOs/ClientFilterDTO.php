<?php

namespace App\DTOs;

use Illuminate\Http\Request;

class ClientFilterDTO
{
    public ?string $search;
    public ?bool $duplicatesOnly;
    public ?string $sortBy;
    public ?string $sortDirection;

    public function __construct(?string $search = null, ?bool $duplicatesOnly = null, ?string $sortBy = 'id', ?string $sortDirection = 'desc')
    {
        $this->search = $search;
        $this->duplicatesOnly = $duplicatesOnly;
        $this->sortBy = $sortBy;
        $this->sortDirection = $sortDirection;
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: $request->input('search'),
            duplicatesOnly: $request->boolean('duplicates_only'),
            sortBy: $request->input('sort_by', 'id'),
            sortDirection: $request->input('sort_direction', 'desc')
        );
    }
}
