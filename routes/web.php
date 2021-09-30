<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DashboardController;

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

require __DIR__ . '/auth.php';
