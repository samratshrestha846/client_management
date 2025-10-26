<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ClientShowService
{
    public function show(int $id): array
    {
        $client = Client::find($id);
        if (is_null($client)) {
            throw new ModelNotFoundException("Client not found.");
        }

        $duplicates = Client::where('company_name', $client->company_name)
            ->where('email', $client->email)
            ->where('phone_number', $client->phone_number)
            ->where('id', '<>', $client->id)
            ->get();

        return [
            'client' => $client,
            'duplicates' => $duplicates,
        ];
    }
}
