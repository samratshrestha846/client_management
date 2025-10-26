<?php

use App\Http\Controllers\Api\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::prefix('clients')->group(function () {
    Route::post('import', [ClientController::class, 'import']);
    Route::get('', [ClientController::class, 'index']);
    Route::get('export', [ClientController::class, 'export']);
    Route::get('{id}', [ClientController::class, 'show']);
});
