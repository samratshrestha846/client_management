<?php

namespace App\Services;

use App\DTOs\ClientExportDTO;
use App\Models\Client;
use Illuminate\Support\Facades\Storage;

class ClientExportService
{
    public function export(ClientExportDTO $dto): string
    {
        $query = Client::query();

        if ($dto->duplicatesOnly) {
            $query->whereIn('id', function ($sub) {
                $sub->selectRaw('MIN(id)')
                    ->from('clients')
                    ->groupBy('company_name', 'email', 'phone_number')
                    ->havingRaw('COUNT(*) > 1');
            });
        }

        $clients = $query->get(['company_name', 'email', 'phone_number']);
        $filename = 'clients_export_' . now()->timestamp . '.csv';
        $path = 'exports/' . $filename;

        Storage::disk('local')->put($path, $this->toCsv($clients));

        return Storage::path($path);
    }

    protected function toCsv($rows): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['company_name', 'email', 'phone_number']);
        foreach ($rows as $row) {
            fputcsv($output, $row->toArray());
        }
        rewind($output);
        return stream_get_contents($output);
    }
}
