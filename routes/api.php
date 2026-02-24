<?php

use App\Http\Controllers\Api\ApiLetterController;
use App\Http\Controllers\Api\FacetsController;
use App\Http\Controllers\Api\v2\AuthController;
use App\Http\Controllers\Api\v2\DatabaseSyncController;
use App\Http\Controllers\Api\v2\LetterController as apiV2LetterController;
use App\Http\Controllers\Api\v2\LocationController as apiV2LocationController;
use App\Http\Controllers\Api\v2\PlaceController as apiV2PlaceController;
use App\Http\Controllers\Api\v2\IdentityController as apiV2IdentityController;
use App\Http\Controllers\Api\v2\GlobalIdentityController as apiV2GlobalIdentityController;
use App\Http\Controllers\Api\v2\GlobalPlaceController as apiV2GlobalPlaceController;
use App\Http\Controllers\Api\v2\GlobalProfessionCategoryController as apiV2GlobalProfessionCategoryController;
use App\Http\Controllers\Api\v2\GlobalProfessionController as apiV2GlobalProfessionController;
use App\Http\Controllers\Api\v2\GlobalKeywordCategoryController as apiV2GlobalKeywordCategoryController;
use App\Http\Controllers\Api\v2\GlobalKeywordController as apiV2GlobalKeywordController;
use App\Http\Controllers\Api\v2\ProfessionCategoryController as apiV2ProfessionCategoryController;
use App\Http\Controllers\Api\v2\ProfessionController as apiV2ProfessionController;
use App\Http\Controllers\Api\v2\KeywordCategoryController as apiV2KeywordCategoryController;
use App\Http\Controllers\Api\v2\KeywordController as apiV2KeywordController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
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

    Route::get('letter/{uuid}/media', [ApiLetterController::class, 'media']);

    Route::get('letters', [ApiLetterController::class, 'index']);
});

// Route::middleware([InitializeTenancyByDomain::class])->group(function () {
//     Route::prefix('v2')->group(function () {
//         Route::post('generate-token', function (Request $request) {
//             $request->validate([
//                 'email' => 'required|email',
//             ]);

//             $user = User::where('email', $request->email)->first();

//             if (!$user) {
//                 return response()->json([
//                     'message' => 'User not found in this tenant.'
//                 ], 404);
//             }

//             $token = $user->createToken('API Token for ' . $user->email)->plainTextToken;

//             return response()->json([
//                 'token' => $token,
//             ]);
//         });

//         Route::get('clear-workflow', function (Request $request) {
//             Artisan::call('config:clear');
//             Artisan::call('cache:clear');
//             Artisan::call('route:clear');

//             return response()->json([
//                 'message' => 'Config cache cleared, route cache cleared, and application cache cleared.',
//             ]);
//         });
//     });
// });

// Auth
Route::middleware([InitializeTenancyByDomain::class])->prefix('v2')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
});

Route::middleware([InitializeTenancyByDomain::class, 'auth:sanctum'])->group(function () {
    Route::prefix('v2')->group(function () {

        Route::middleware('throttle:api-read')->group(function () {
            // Letters
            Route::get('letters', [apiV2LetterController::class, 'index']);
            Route::get('letter/{id}', [apiV2LetterController::class, 'show']);

            // Locations
            Route::get('locations', [apiV2LocationController::class, 'index']);
            Route::get('location/{id}', [apiV2LocationController::class, 'show']);

            // Global Places
            Route::get('global-places', [apiV2GlobalPlaceController::class, 'index']);
            Route::get('global-place/{id}', [apiV2GlobalPlaceController::class, 'show']);

            // Places
            Route::get('places', [apiV2PlaceController::class, 'index']);
            Route::get('place/{id}', [apiV2PlaceController::class, 'show']);

            // Identities
            Route::get('identities', [apiV2IdentityController::class, 'index']);
            Route::get('identity/{id}', [apiV2IdentityController::class, 'show']);

            // Global Identities
            Route::get('global-identities', [apiV2GlobalIdentityController::class, 'index']);
            Route::get('global-identity/{id}', [apiV2GlobalIdentityController::class, 'show']);
            Route::get('global-identity/{id}/linked-identities', [apiV2GlobalIdentityController::class, 'linkedIdentities']);

            // Global profession categories
            Route::get('global-profession-categories', [apiV2GlobalProfessionCategoryController::class, 'index']);
            Route::get('global-profession-category/{id}', [apiV2GlobalProfessionCategoryController::class, 'show']);

            // Global professions
            Route::get('global-professions', [apiV2GlobalProfessionController::class, 'index']);
            Route::get('global-profession/{id}', [apiV2GlobalProfessionController::class, 'show']);

            // Profession categories
            Route::get('profession-categories', [apiV2ProfessionCategoryController::class, 'index']);
            Route::get('profession-category/{id}', [apiV2ProfessionCategoryController::class, 'show']);

            // Professions
            Route::get('professions', [apiV2ProfessionController::class, 'index']);
            Route::get('profession/{id}', [apiV2ProfessionController::class, 'show']);

            // Global keyword categories
            Route::get('global-keyword-categories', [apiV2GlobalKeywordCategoryController::class, 'index']);
            Route::get('global-keyword-category/{id}', [apiV2GlobalKeywordCategoryController::class, 'show']);

            // Global keywords
            Route::get('global-keywords', [apiV2GlobalKeywordController::class, 'index']);
            Route::get('global-keyword/{id}', [apiV2GlobalKeywordController::class, 'show']);

            // Keyword categories
            Route::get('keyword-categories', [apiV2KeywordCategoryController::class, 'index']);
            Route::get('keyword-category/{id}', [apiV2KeywordCategoryController::class, 'show']);

            // Keywords
            Route::get('keywords', [apiV2KeywordController::class, 'index']);
            Route::get('keyword/{id}', [apiV2KeywordController::class, 'show']);
        });

        Route::middleware('throttle:api-write')->group(function () {
            // Developer Tools
            Route::post('dev/sync-database', DatabaseSyncController::class);

            // Letters
            Route::post('letters', [apiV2LetterController::class, 'store']);
            Route::put('letter/{id}', [apiV2LetterController::class, 'update']);
            // Route::delete('letter/{id}', [apiV2LetterController::class, 'destroy']);

            // Locations
            Route::post('locations', [apiV2LocationController::class, 'store']);
            Route::put('location/{id}', [apiV2LocationController::class, 'update']);
            // Route::delete('location/{id}', [apiV2LocationController::class, 'destroy']);

            // Global Places
            Route::post('global-places', [apiV2GlobalPlaceController::class, 'store'])->middleware('can:manage-users');
            Route::put('global-place/{id}', [apiV2GlobalPlaceController::class, 'update'])->middleware('can:manage-users');
            // Route::delete('global-place/{id}', [apiV2GlobalPlaceController::class, 'destroy']);

            // Places
            Route::post('places', [apiV2PlaceController::class, 'store']);
            Route::put('place/{id}', [apiV2PlaceController::class, 'update']);
            // Route::delete('place/{id}', [apiV2PlaceController::class, 'destroy']);

            // Identities
            Route::post('identities', [apiV2IdentityController::class, 'store']);
            Route::put('identity/{id}', [apiV2IdentityController::class, 'update']);
            // Route::delete('identity/{id}', [apiV2IdentityController::class, 'destroy']);

            // Global Identities
            Route::post('global-identities', [apiV2GlobalIdentityController::class, 'store'])->middleware('can:manage-users');
            Route::put('global-identity/{id}', [apiV2GlobalIdentityController::class, 'update'])->middleware('can:manage-users');
            // Route::delete('global-identity/{id}', [apiV2GlobalIdentityController::class, 'destroy']);

            // Global profession categories
            Route::post('global-profession-categories', [apiV2GlobalProfessionCategoryController::class, 'store'])->middleware('can:manage-users');
            Route::put('global-profession-category/{id}', [apiV2GlobalProfessionCategoryController::class, 'update'])->middleware('can:manage-users');
            // Route::delete('global-profession-category/{id}', [apiV2GlobalProfessionCategoryController::class, 'destroy']);

            // Global professions
            Route::post('global-professions', [apiV2GlobalProfessionController::class, 'store'])->middleware('can:manage-users');
            Route::put('global-profession/{id}', [apiV2GlobalProfessionController::class, 'update'])->middleware('can:manage-users');
            // Route::delete('global-profession/{id}', [apiV2GlobalProfessionController::class, 'destroy']);

            // Profession categories
            Route::post('profession-categories', [apiV2ProfessionCategoryController::class, 'store']);
            Route::put('profession-category/{id}', [apiV2ProfessionCategoryController::class, 'update']);
            // Route::delete('profession-category/{id}', [apiV2ProfessionCategoryController::class, 'destroy']);

            // Professions
            Route::post('professions', [apiV2ProfessionController::class, 'store']);
            Route::put('profession/{id}', [apiV2ProfessionController::class, 'update']);
            // Route::delete('profession/{id}', [apiV2ProfessionController::class, 'destroy']);

            // Global keyword categories
            Route::post('global-keyword-categories', [apiV2GlobalKeywordCategoryController::class, 'store'])->middleware('can:manage-users');
            Route::put('global-keyword-category/{id}', [apiV2GlobalKeywordCategoryController::class, 'update'])->middleware('can:manage-users');
            // Route::delete('global-keyword-category/{id}', [apiV2GlobalKeywordCategoryController::class, 'destroy']);

            // Global keywords
            Route::post('global-keywords', [apiV2GlobalKeywordController::class, 'store'])->middleware('can:manage-users');
            Route::put('global-keyword/{id}', [apiV2GlobalKeywordController::class, 'update'])->middleware('can:manage-users');
            // Route::delete('global-keyword/{id}', [apiV2GlobalKeywordController::class, 'destroy']);

            // Keyword categories
            Route::post('keyword-categories', [apiV2KeywordCategoryController::class, 'store']);
            Route::put('keyword-category/{id}', [apiV2KeywordCategoryController::class, 'update']);
            // Route::delete('keyword-category/{id}', [apiV2KeywordCategoryController::class, 'destroy']);

            // Keywords
            Route::post('keywords', [apiV2KeywordController::class, 'store']);
            Route::put('keyword/{id}', [apiV2KeywordController::class, 'update']);
            // Route::delete('keyword/{id}', [apiV2KeywordController::class, 'destroy']);
        });

    });
});
