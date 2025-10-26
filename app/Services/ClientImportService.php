<?php

namespace App\Services;

use App\DTOs\ClientImportRowDTO;
use App\Models\Client;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ClientImportService
{
    protected int $batchSize = 500;

    public function importCsv(UploadedFile $file, ?string $importBatchId = null): array
    {
        $importBatchId ??= (string) Str::uuid();
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return ['error' => 'Unable to open uploaded file.'];
        }

        $header = $this->readHeader($handle);
        $existingKeys = $this->getExistingCanonicalKeyMap();

        $batch = [];
        $failedRows = [];
        $insertedIds = [];

        $rowNum = 1;
        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $rowNum++;
            $assoc = $this->mapRowToAssoc($header, $row);
            $dto = new ClientImportRowDTO($assoc, $rowNum);

            if (!$this->validateRow($dto, $failedRows)) {
                continue;
            }

            $canonical = Client::canonicalKey($dto->toArray());

            if (isset($existingKeys[$canonical])) {
                continue;
            }

            $batch[] = $this->formatRowForInsert($dto, $importBatchId);

            if (count($batch) >= $this->batchSize) {
                $this->flushBatchInsert($batch, $existingKeys, $insertedIds);
            }
        }

        if ($batch) {
            $this->flushBatchInsert($batch, $existingKeys, $insertedIds);
        }

        fclose($handle);

        return [
            'import_batch_id' => $importBatchId,
            'imported_count' => count($insertedIds),
            'failed_rows' => $failedRows,
            'duplicate_groups' => $this->buildDuplicateGroups($importBatchId),
        ];
    }

    protected function readHeader($handle): array
    {
        $raw = fgetcsv($handle, 0, ',');
        return array_map(fn($h) => mb_strtolower(trim($h)), $raw);
    }

    protected function mapRowToAssoc(array $header, array $row): array
    {
        $assoc = [];
        foreach ($header as $i => $colName) {
            $assoc[$colName] = $row[$i] ?? null;
        }
        return $assoc;
    }

    protected function validateRow(ClientImportRowDTO $dto, array &$failedRows): bool
    {
        $validator = Validator::make($dto->toArray(), [
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
        ]);

        if ($validator->fails()) {
            $failedRows[$dto->rowNumber] = $validator->errors()->all();
            return false;
        }
        return true;
    }

    protected function formatRowForInsert(ClientImportRowDTO $dto, string $importBatchId): array
    {
        return [
            'company_name' => $dto->company_name,
            'email' => $dto->email,
            'phone_number' => $dto->phone_number,
            'import_batch_id' => $importBatchId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    protected function getExistingCanonicalKeyMap(): array
    {
        $map = [];
        Client::select('id', 'company_name', 'email', 'phone_number')
            ->chunk(2000, function ($rows) use (&$map) {
                foreach ($rows as $r) {
                    $map[Client::canonicalKey($r->toArray())] = $r->id;
                }
            });
        return $map;
    }

    protected function flushBatchInsert(array &$batch, array &$existingKeys, array &$insertedIds): void
    {
        DB::table('clients')->insert($batch);

        $importBatchId = $batch[0]['import_batch_id'] ?? null;
        $inserted = Client::where('import_batch_id', $importBatchId)
            ->orderByDesc('id')
            ->take(count($batch))
            ->get();

        foreach ($inserted as $client) {
            $key = Client::canonicalKey($client->toArray());
            $existingKeys[$key] = $client->id;
            $insertedIds[] = $client->id;
        }

        $batch = [];
    }

    protected function buildDuplicateGroups(?string $importBatchId = null): array
    {
        $groups = [];

        $query = Client::query();
        if ($importBatchId) {
            $query->where('import_batch_id', $importBatchId);
        }

        $query->chunk(2000, function ($rows) use (&$groups) {
            foreach ($rows as $r) {
                $key = Client::canonicalKey($r->toArray());
                $groups[$key][] = $r;
            }
        });

        $result = [];
        foreach ($groups as $members) {
            if (count($members) > 1) {
                $rootId = $members[0]->id;
                $result[$rootId] = array_map(fn($m) => $m->toArray(), $members);
            }
        }

        return $result;
    }
}
