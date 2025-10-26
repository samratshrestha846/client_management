<?php
namespace App\Services;

use App\DTOs\ClientImportRowDTO;
use App\Models\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class ClientImportService
{
    protected int $batchSize = 500;

    public function importCsv(UploadedFile $file, ?string $importBatchId = null): array
    {
        $importBatchId = $importBatchId ?? (string) Str::uuid();
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return ['error' => 'Unable to open uploaded file.'];
        }

        $header = null;
        $rowNum = 0;
        $batch = [];
        $insertedIds = [];
        $failedRows = [];
        $duplicateMap = [];
        $rowToDuplicateGroup = [];

        $existingKeys = $this->getExistingCanonicalKeyMap();

        while (($raw = fgetcsv($handle, 0, ',')) !== false) {
            $rowNum++;
            if ($rowNum === 1) {
                $header = array_map(fn($h) => mb_strtolower(trim($h)), $raw);
                continue;
            }

            $assoc = [];
            foreach ($header as $i => $colName) {
                $assoc[$colName] = $raw[$i] ?? null;
            }

            $dto = new ClientImportRowDTO($assoc, $rowNum);

            $validator = Validator::make($dto->toArray(), [
                'company_name' => ['required', 'string', 'max:255'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone_number' => ['nullable', 'string', 'max:50'],
            ]);

            if ($validator->fails()) {
                $failedRows[$rowNum] = $validator->errors()->all();
                continue;
            }

            $canonical = Client::canonicalKey($dto->toArray());

            if (isset($existingKeys[$canonical])) {
                $groupId = $existingKeys[$canonical];
                $rowToDuplicateGroup[$rowNum] = $groupId;
            } else {
                if (isset($duplicateMap[$canonical])) {
                    $groupId = $duplicateMap[$canonical][0];
                    $rowToDuplicateGroup[$rowNum] = $groupId ?? null;
                } else {
                    $batch[] = [
                        'company_name' => $dto->company_name,
                        'email' => $dto->email,
                        'phone_number' => $dto->phone_number,
                        'import_batch_id' => $importBatchId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $duplicateMap[$canonical] = [];
                }
            }

            if (count($batch) >= $this->batchSize) {
                $this->flushBatchInsert($batch, $existingKeys, $duplicateMap, $insertedIds);
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            $this->flushBatchInsert($batch, $existingKeys, $duplicateMap, $insertedIds);
        }

        fclose($handle);

        $duplicateGroups = $this->buildDuplicateGroups($importBatchId);

        $importedCount = count($insertedIds);
        $report = [
            'imported_count' => $importedCount,
            'failed_rows' => $failedRows,
            'duplicate_groups' => $duplicateGroups,
            'import_batch_id' => $importBatchId,
        ];

        return $report;
    }


    protected function getExistingCanonicalKeyMap(): array
    {
        $map = [];
        Client::select('id', 'company_name', 'email', 'phone_number')
            ->orderBy('id')
            ->chunk(2000, function($rows) use (&$map) {
                foreach ($rows as $r) {
                    $key = Client::canonicalKey([
                        'company_name' => $r->company_name,
                        'email' => $r->email,
                        'phone_number' => $r->phone_number,
                    ]);
                    if (!isset($map[$key])) {
                        $map[$key] = $r->id;
                    }
                }
            });
        return $map;
    }

    protected function flushBatchInsert(array $batch, array &$existingKeys, array &$duplicateMap, array &$insertedIds)
    {
        if (empty($batch)) {
            return;
        }

        DB::table('clients')->insert($batch);

        $importBatchId = $batch[0]['import_batch_id'] ?? null;
        $fetched = Client::where('import_batch_id', $importBatchId)
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($fetched as $f) {
            $key = Client::canonicalKey([
                'company_name' => $f->company_name,
                'email' => $f->email,
                'phone_number' => $f->phone_number,
            ]);
            if (!isset($existingKeys[$key])) {
                if (empty($duplicateMap[$key])) {
                    $existingKeys[$key] = $f->id;

                    $duplicateMap[$key][] = $f->id;
                } else {
                    $rootId = $existingKeys[$key] ?? $duplicateMap[$key][0] ?? $f->id;
                    $f->duplicate_group_id = $rootId;
                    $f->saveQuietly();
                    $duplicateMap[$key][] = $f->id;
                }
            } else {
                $rootId = $existingKeys[$key];
                $f->duplicate_group_id = $rootId;
                $f->saveQuietly();
                $duplicateMap[$key][] = $f->id;
            }
            $insertedIds[] = $f->id;
        }
    }

    protected function buildDuplicateGroups(string $importBatchId = null): array
    {
        $groups = [];

        $qb = Client::query();
        if ($importBatchId) {
            $qb->where('import_batch_id', $importBatchId);
        }

        $qb->chunk(2000, function($rows) use (&$groups) {
            foreach ($rows as $r) {
                $key = Client::canonicalKey([
                    'company_name' => $r->company_name,
                    'email' => $r->email,
                    'phone_number' => $r->phone_number,
                ]);
                $groups[$key][] = $r;
            }
        });

        $result = [];
        foreach ($groups as $key => $members) {
            if (count($members) > 1) {
                usort($members, fn($a, $b) => $a->id <=> $b->id);
                $rootId = $members[0]->id;
                $result[$rootId] = array_map(fn($m) => $m->toArray(), $members);
            }
        }

        return $result;
    }
}
