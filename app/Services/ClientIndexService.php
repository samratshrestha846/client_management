<?php

namespace App\Services;

use App\DTOs\ClientFilterDTO;
use App\Models\Client;

class ClientIndexService
{
    protected array $sortableColumns = [
        'id', 'company_name', 'email', 'phone_number', 'created_at', 'updated_at'
    ];

    public function list(ClientFilterDTO $filter)
    {
        $query = Client::query();

        if ($filter->search) {
            $query->where(function ($q) use ($filter) {
                $q->where('company_name', 'like', "%{$filter->search}%")
                  ->orWhere('email', 'like', "%{$filter->search}%")
                  ->orWhere('phone_number', 'like', "%{$filter->search}%");
            });
        }

        if ($filter->duplicatesOnly) {
            $duplicates = Client::selectRaw('company_name, email, phone_number, COUNT(*) as count')
                ->groupBy('company_name', 'email', 'phone_number')
                ->having('count', '>', 1)
                ->pluck('count', 'company_name');

            $query->whereIn('company_name', $duplicates->keys());
        }

        $sortBy = $this->isSortable($filter->sortBy)
            ? $filter->sortBy
            : 'id';

        $sortDirection = in_array(strtolower($filter->sortDirection), ['asc', 'desc'])
            ? $filter->sortDirection
            : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate(25);
    }

    protected function isSortable(string $column): bool
    {
        return in_array($column, $this->sortableColumns, true);
    }
}
