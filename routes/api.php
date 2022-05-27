<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FacetsController;
use App\Http\Controllers\Api\ApiLetterController;
use App\Http\Controllers\Api\ModsExportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('facets', FacetsController::class);

Route::get('letter/{uuid}', [ApiLetterController::class, 'show']);

Route::get('letters', [ApiLetterController::class, 'index']);

Route::get('mods-export', ModsExportController::class);
