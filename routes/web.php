<?php

use App\Http\Controllers\Ajax\AjaxProfessionCategoryController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfessionController;
use App\Http\Controllers\KeywordCategoryController;
use App\Http\Controllers\ProfessionCategoryController;
use App\Http\Controllers\Ajax\AjaxProfessionController;
use App\Http\Controllers\Ajax\KeywordCategoryController as AjaxKeywordCategoryController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::redirect('/', '/dashboard');

Route::get('/dashboard', DashboardController::class)
    ->name('dashboard')
    ->middleware('auth');

Route::prefix('users')->group(function () {
    Route::get('/', [UserController::class, 'index'])
        ->name('users')
        ->middleware(['auth', 'can:manage-users']);

    Route::get('/create', [UserController::class, 'create'])
        ->name('users.create')
        ->middleware(['auth', 'can:manage-users']);

    Route::get('/{user}/edit', [UserController::class, 'edit'])
        ->name('users.edit')
        ->middleware(['auth', 'can:manage-users']);

    Route::post('/', [UserController::class, 'store'])
        ->name('users.store')
        ->middleware(['auth', 'can:manage-users']);

    Route::put('/{user}', [UserController::class, 'update'])
        ->name('users.update')
        ->middleware(['auth', 'can:manage-users']);

    Route::delete('/{user}', [UserController::class, 'destroy'])
        ->name('users.destroy')
        ->middleware(['auth', 'can:manage-users']);
});

Route::prefix('locations')->group(function () {
    Route::get('/', [LocationController::class, 'index'])
        ->name('locations')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/create', [LocationController::class, 'create'])
        ->name('locations.create')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/{location}/edit', [LocationController::class, 'edit'])
        ->name('locations.edit')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::post('/', [LocationController::class, 'store'])
        ->name('locations.store')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::put('/{location}', [LocationController::class, 'update'])
        ->name('locations.update')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::delete('/{location}', [LocationController::class, 'destroy'])
        ->name('locations.destroy')
        ->middleware(['auth', 'can:manage-metadata']);
});

Route::prefix('professions')->group(function () {
    Route::get('/', [ProfessionController::class, 'index'])
        ->name('professions')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/create', [ProfessionController::class, 'create'])
        ->name('professions.create')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/{profession}/edit', [ProfessionController::class, 'edit'])
        ->name('professions.edit')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::post('/', [ProfessionController::class, 'store'])
        ->name('professions.store')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::put('/{profession}', [ProfessionController::class, 'update'])
        ->name('professions.update')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::delete('/{profession}', [ProfessionController::class, 'destroy'])
        ->name('professions.destroy')
        ->middleware(['auth', 'can:manage-metadata']);
});

Route::prefix('professions/category')->group(function () {
    Route::get('/', function () {
        return redirect()->route('professions');
    });

    Route::get('/create', [ProfessionCategoryController::class, 'create'])
        ->name('professions.category.create')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/{professionCategory}/edit', [ProfessionCategoryController::class, 'edit'])
        ->name('professions.category.edit')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::post('/', [ProfessionCategoryController::class, 'store'])
        ->name('professions.category.store')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::put('/{professionCategory}', [ProfessionCategoryController::class, 'update'])
        ->name('professions.category.update')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::delete('/{professionCategory}', [ProfessionCategoryController::class, 'destroy'])
        ->name('professions.category.destroy')
        ->middleware(['auth', 'can:manage-metadata']);
});

Route::prefix('keywords')->group(function () {
    Route::get('/', [KeywordController::class, 'index'])
        ->name('keywords')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/create', [KeywordController::class, 'create'])
        ->name('keywords.create')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/{keyword}/edit', [KeywordController::class, 'edit'])
        ->name('keywords.edit')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::post('/', [KeywordController::class, 'store'])
        ->name('keywords.store')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::put('/{keyword}', [KeywordController::class, 'update'])
        ->name('keywords.update')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::delete('/{keyword}', [KeywordController::class, 'destroy'])
        ->name('keywords.destroy')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/export', [KeywordController::class, 'export'])
        ->name('keywords.export')
        ->middleware(['auth', 'can:manage-metadata']);
});

Route::prefix('keywords/category')->group(function () {
    Route::get('/', function () {
        return redirect()->route('keywords');
    });

    Route::get('/create', [KeywordCategoryController::class, 'create'])
        ->name('keywords.category.create')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/{keywordCategory}/edit', [KeywordCategoryController::class, 'edit'])
        ->name('keywords.category.edit')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::post('/', [KeywordCategoryController::class, 'store'])
        ->name('keywords.category.store')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::put('/{keywordCategory}', [KeywordCategoryController::class, 'update'])
        ->name('keywords.category.update')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::delete('/{keywordCategory}', [KeywordCategoryController::class, 'destroy'])
        ->name('keywords.category.destroy')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/export', [KeywordCategoryController::class, 'export'])
        ->name('keywords.category.export')
        ->middleware(['auth', 'can:manage-metadata']);
});

Route::prefix('places')->group(function () {
    Route::get('/', [PlaceController::class, 'index'])
        ->name('places')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/create', [PlaceController::class, 'create'])
        ->name('places.create')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/{place}/edit', [PlaceController::class, 'edit'])
        ->name('places.edit')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::post('/', [PlaceController::class, 'store'])
        ->name('places.store')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::put('/{place}', [PlaceController::class, 'update'])
        ->name('places.update')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::delete('/{place}', [PlaceController::class, 'destroy'])
        ->name('places.destroy')
        ->middleware(['auth', 'can:manage-metadata']);
});

Route::prefix('identities')->group(function () {
    Route::get('/', [IdentityController::class, 'index'])
        ->name('identities')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/create', [IdentityController::class, 'create'])
        ->name('identities.create')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/{identity}/edit', [IdentityController::class, 'edit'])
        ->name('identities.edit')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::post('/', [IdentityController::class, 'store'])
        ->name('identities.store')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::put('/{identity}', [IdentityController::class, 'update'])
        ->name('identities.update')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::delete('/{identity}', [IdentityController::class, 'destroy'])
        ->name('identities.destroy')
        ->middleware(['auth', 'can:manage-metadata']);
});

Route::prefix('ajax')->group(function () {
    Route::get('/keyword-category', AjaxKeywordCategoryController::class)
        ->name('ajax.keywords.category')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/professions', AjaxProfessionController::class)
        ->name('ajax.professions')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('/profession-category', AjaxProfessionCategoryController::class)
        ->name('ajax.professions.category')
        ->middleware(['auth', 'can:manage-metadata']);
});

require __DIR__ . '/auth.php';
