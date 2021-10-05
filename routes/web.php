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

// users
Route::get('/users', [UserController::class, 'index'])
    ->name('users')
    ->middleware(['auth', 'can:manage-users']);

Route::get('/users/create', [UserController::class, 'create'])
    ->name('users.create')
    ->middleware(['auth', 'can:manage-users']);

Route::get('/users/{user}/edit', [UserController::class, 'edit'])
    ->name('users.edit')
    ->middleware(['auth', 'can:manage-users']);

Route::post('/users', [UserController::class, 'store'])
    ->name('users.store')
    ->middleware(['auth', 'can:manage-users']);

Route::put('/users/{user}', [UserController::class, 'update'])
    ->name('users.update')
    ->middleware(['auth', 'can:manage-users']);

Route::delete('/users/{user}', [UserController::class, 'destroy'])
    ->name('users.destroy')
    ->middleware(['auth', 'can:manage-users']);

// locations
Route::get('/locations', [LocationController::class, 'index'])
    ->name('locations')
    ->middleware(['auth', 'can:manage-metadata']);

Route::get('/locations/create', [LocationController::class, 'create'])
    ->name('locations.create')
    ->middleware(['auth', 'can:manage-metadata']);

Route::get('/locations/{location}/edit', [LocationController::class, 'edit'])
    ->name('locations.edit')
    ->middleware(['auth', 'can:manage-metadata']);

Route::post('/locations', [LocationController::class, 'store'])
    ->name('locations.store')
    ->middleware(['auth', 'can:manage-metadata']);

Route::put('/locations/{location}', [LocationController::class, 'update'])
    ->name('locations.update')
    ->middleware(['auth', 'can:manage-metadata']);

Route::delete('/locations/{location}', [LocationController::class, 'destroy'])
    ->name('locations.destroy')
    ->middleware(['auth', 'can:manage-metadata']);

// professions
Route::get('/professions', [ProfessionController::class, 'index'])
    ->name('professions')
    ->middleware(['auth', 'can:manage-metadata']);

Route::get('/professions/create', [ProfessionController::class, 'create'])
    ->name('professions.create')
    ->middleware(['auth', 'can:manage-metadata']);

Route::get('/professions/{profession}/edit', [ProfessionController::class, 'edit'])
    ->name('professions.edit')
    ->middleware(['auth', 'can:manage-metadata']);

Route::post('/professions', [ProfessionController::class, 'store'])
    ->name('professions.store')
    ->middleware(['auth', 'can:manage-metadata']);

Route::put('/professions/{profession}', [ProfessionController::class, 'update'])
    ->name('professions.update')
    ->middleware(['auth', 'can:manage-metadata']);

Route::delete('/professions/{profession}', [ProfessionController::class, 'destroy'])
    ->name('professions.destroy')
    ->middleware(['auth', 'can:manage-metadata']);

require __DIR__ . '/auth.php';
