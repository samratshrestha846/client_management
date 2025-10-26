<?php

namespace Tests\Feature;

use App\Models\Client;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Helpers\CsvHelper;
use PHPUnit\Framework\Attributes\Test;

class ClientManagementTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_import_large_csv_and_detect_duplicates()
    {
        Storage::fake('local');

        $file = CsvHelper::generateCsv(5000, 0.1);

        $response = $this->postJson('/api/clients/import', [
            'file' => $file
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'imported_count',
                     'failed_rows',
                     'duplicate_groups',
                     'import_batch_id',
                 ]);

        $json = $response->json();

        $this->assertGreaterThan(0, $json['imported_count']);
        $this->assertIsArray($json['duplicate_groups']);
        $this->assertLessThanOrEqual(5000, Client::count());
    }

    #[Test]
    public function it_can_list_clients_with_filters_and_sorting()
    {
        $clients = Client::factory()->count(20)->create();
        $firstCompany = $clients[0]->company_name;

        $response = $this->getJson("/api/clients?search={$firstCompany}&sort_by=company_name&sort_direction=asc");

        $response->assertStatus(200)
                 ->assertJsonStructure(['data']);

        $this->assertStringContainsString($firstCompany, $response->json('data')[0]['company_name']);
    }

    #[Test]
    public function it_can_show_client_and_their_duplicates()
    {
        $client1 = Client::factory()->create([
            'company_name' => 'Duplicate Co',
            'email' => 'dup@example.com',
            'phone_number' => '1111'
        ]);

        $client1->duplicate_group_id = $client1->id;
        $client1->save();

        $client2 = Client::factory()->duplicateOf($client1)->create([
            'duplicate_group_id' => $client1->id
        ]);

        $response = $this->getJson("/api/clients/{$client1->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure(['client','duplicates']);

        $this->assertCount(1, $response->json('duplicates'));
        $this->assertEquals($client2->id, $response->json('duplicates')[0]['id']);
    }

    #[Test]
    public function it_can_export_duplicates_only()
    {
        $dup1 = Client::factory()->create([
            'company_name' => 'Duplicate Co',
            'email' => 'duplicate@example.com',
            'phone_number' => '1234567890',
        ]);

        Client::factory()->create([
            'company_name' => 'Duplicate Co',
            'email' => 'duplicate@example.com',
            'phone_number' => '1234567890',
            'duplicate_group_id' => $dup1->id,
        ]);

        Client::factory()->create([
            'company_name' => 'Unique Co',
            'email' => 'unique@example.com',
            'phone_number' => '0987654321',
        ]);
        $response = $this->get('/api/clients/export?duplicates_only=1');

        $response->assertStatus(200);

        $csvPath = $response->baseResponse->getFile()->getRealPath();
        $csvContent = file_get_contents($csvPath);

        $this->assertStringContainsString('Duplicate Co', $csvContent);
        $this->assertStringNotContainsString('Unique Co', $csvContent);
    }


    #[Test]
    public function it_can_import_massive_csv_stress_test()
    {
        $file = CsvHelper::generateCsv(10000, 0.05);

        $response = $this->postJson('/api/clients/import', ['file' => $file]);

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(10000, Client::count());
    }
}
