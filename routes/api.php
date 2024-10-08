<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\FacetsController;
use App\Http\Controllers\Api\ApiLetterController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;

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

Route::middleware([InitializeTenancyByDomain::class, 'web'])->group(function () {
    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('facets', FacetsController::class);

    Route::get('letter/{uuid}', [ApiLetterController::class, 'show']);

    Route::get('letters', [ApiLetterController::class, 'index']);

});
