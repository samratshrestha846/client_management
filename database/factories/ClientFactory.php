<?php

namespace Database\Factories;

use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition()
    {
        return [
            'company_name' => $this->faker->company,
            'email' => $this->faker->unique()->safeEmail,
            'phone_number' => $this->faker->unique()->phoneNumber,
            'duplicate_group_id' => null,
        ];
    }

    public function duplicateOf(Client $client)
    {
        return $this->state(function () use ($client) {
            return [
                'company_name' => $client->company_name,
                'email' => $client->email,
                'phone_number' => $client->phone_number,
            ];
        });
    }
}
