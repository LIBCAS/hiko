<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfessionController;

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

require __DIR__ . '/auth.php';
