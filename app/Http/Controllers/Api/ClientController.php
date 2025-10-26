<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ClientExportDTO;
use App\DTOs\ClientFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ClientImportRequest;
use App\Services\ClientExportService;
use App\Services\ClientImportService;
use App\Services\ClientIndexService;
use App\Services\ClientShowService;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    protected ClientImportService $importService;

    public function __construct(ClientImportService $importService)
    {
        $this->importService = $importService;
    }

    public function import(ClientImportRequest $request)
    {
        $file = $request->file('file');
        $report = $this->importService->importCsv($file);

        return response()->json($report, 200);
    }

    public function index(Request $request, ClientIndexService $service)
    {
        $filter = ClientFilterDTO::fromRequest($request);
        return response()->json($service->list($filter));
    }

    public function show($id, ClientShowService $service)
    {
        return response()->json($service->show($id));
    }

    public function export(Request $request, ClientExportService $service)
    {
        $dto = ClientExportDTO::fromRequest($request);
        $path = $service->export($dto);

        return response()->download($path)->deleteFileAfterSend(true);
    }
}
