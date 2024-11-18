<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    UserController,
    ImageController,
    ImageShowController,
    MergeController,
    PlaceController,
    LetterController,
    AccountController,
    KeywordController,
    DevToolsController,
    EditLinkController,
    IdentityController,
    LanguageController,
    LocationController,
    ProfessionController,
    ProfessionCategoryController,
    GlobalProfessionController,
    GlobalProfessionCategoryController,
    LetterPreviewController,
    KeywordCategoryController,
    LetterComparisonController
};
use App\Http\Controllers\Ajax\{
    AjaxPlaceController,
    AjaxKeywordController,
    AjaxIdentityController,
    SimilarItemsController,
    SimilarNamesController,
    SimilarPlacesController,
    AjaxProfessionController,
    SimilarLocationsController,
    AjaxKeywordCategoryController,
    AjaxProfessionCategoryController,
    AjaxGlobalProfessionCategoryController,
    AjaxLetterComparisonController
};
use Stancl\Tenancy\Middleware\{
    InitializeTenancyByDomain,
    PreventAccessFromCentralDomains
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the 
| RouteServiceProvider within a group containing the "web" middleware group.
|
*/

Route::middleware([InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class, 'web'])->group(function () {

    Route::get('/', function () {
        return \Illuminate\Support\Facades\Redirect::route('letters');
    });    
    
    /*
    |--------------------------------------------------------------------------
    | Users
    |--------------------------------------------------------------------------
    */
    Route::prefix('users')->middleware(['auth', 'can:manage-users'])->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('users');
        Route::get('create', [UserController::class, 'create'])->name('users.create');
        Route::get('{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
        Route::put('{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    /*
    |--------------------------------------------------------------------------
    | Locations
    |--------------------------------------------------------------------------
    */
    Route::prefix('locations')->middleware(['auth'])->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('locations')->middleware('can:view-metadata');
        Route::get('create', [LocationController::class, 'create'])->name('locations.create')->middleware('can:manage-metadata');
        Route::get('{location}/edit', [LocationController::class, 'edit'])->name('locations.edit')->middleware('can:manage-metadata');
        Route::post('/', [LocationController::class, 'store'])->name('locations.store')->middleware('can:manage-metadata');
        Route::put('{location}', [LocationController::class, 'update'])->name('locations.update')->middleware('can:manage-metadata');
        Route::delete('{location}', [LocationController::class, 'destroy'])->name('locations.destroy')->middleware('can:delete-metadata');
        Route::get('export', [LocationController::class, 'export'])->name('locations.export')->middleware('can:manage-metadata');
    });

    /*
    |--------------------------------------------------------------------------
    | Professions
    |--------------------------------------------------------------------------
    */
    Route::prefix('professions')->middleware(['auth', 'can:view-metadata'])->group(function () {
        Route::get('/', [ProfessionController::class, 'index'])->name('professions');
        Route::get('create', [ProfessionController::class, 'create'])->name('professions.create')->middleware('can:manage-metadata');
        Route::get('{profession}/edit', [ProfessionController::class, 'edit'])->name('professions.edit')->middleware('can:manage-metadata');
        Route::post('/', [ProfessionController::class, 'store'])->name('professions.store')->middleware('can:manage-metadata');
        Route::put('{profession}', [ProfessionController::class, 'update'])->name('professions.update')->middleware('can:manage-metadata');
        Route::delete('{profession}', [ProfessionController::class, 'destroy'])->name('professions.destroy')->middleware('can:delete-metadata');
        Route::get('export', [ProfessionController::class, 'export'])->name('professions.export')->middleware('can:manage-metadata');
    });

    /*
    |--------------------------------------------------------------------------
    | Profession Categories
    |--------------------------------------------------------------------------
    */
    Route::prefix('professions/category')->middleware(['auth', 'can:manage-metadata'])->group(function () {
        Route::get('/', [ProfessionCategoryController::class, 'index'])->name('professions.category');
        Route::get('create', [ProfessionCategoryController::class, 'create'])->name('professions.category.create');
        Route::get('{professionCategory}/edit', [ProfessionCategoryController::class, 'edit'])->name('professions.category.edit');
        Route::post('/', [ProfessionCategoryController::class, 'store'])->name('professions.category.store');
        Route::put('{professionCategory}', [ProfessionCategoryController::class, 'update'])->name('professions.category.update');
        Route::delete('{professionCategory}', [ProfessionCategoryController::class, 'destroy'])->name('professions.category.destroy');
        Route::get('export', [ProfessionCategoryController::class, 'export'])->name('professions.category.export');
        Route::get('{id}/attach', [ProfessionCategoryController::class, 'attachProfession'])->name('professions.attach');
        Route::post('{category}/attach', [ProfessionCategoryController::class, 'storeAttachedProfession'])->name('professions.category.attach');
    });

    /*
    |--------------------------------------------------------------------------
    | Global Professions
    |--------------------------------------------------------------------------
    */
    Route::prefix('global-professions')->middleware(['auth'])->name('global.professions.')->group(function () {
        Route::get('/', [GlobalProfessionController::class, 'index'])->name('index')->middleware('can:view-users');
        Route::get('create', [GlobalProfessionController::class, 'create'])->name('create')->middleware('can:manage-users');
        Route::get('{globalProfession}/edit', [GlobalProfessionController::class, 'edit'])->name('edit')->middleware('can:manage-users');
        Route::post('/', [GlobalProfessionController::class, 'store'])->name('store')->middleware('can:manage-users');
        Route::put('{globalProfession}', [GlobalProfessionController::class, 'update'])->name('update')->middleware('can:manage-users');
        Route::delete('{globalProfession}', [GlobalProfessionController::class, 'destroy'])->name('destroy')->middleware('can:delete-users');
    });

    /*
    |--------------------------------------------------------------------------
    | Keywords
    |--------------------------------------------------------------------------
    */
    Route::prefix('keywords')->middleware(['auth'])->group(function () {
        Route::get('/', [KeywordController::class, 'index'])->name('keywords')->middleware('can:view-metadata');
        Route::get('create', [KeywordController::class, 'create'])->name('keywords.create')->middleware('can:manage-metadata');
        Route::get('{keyword}/edit', [KeywordController::class, 'edit'])->name('keywords.edit')->middleware('can:manage-metadata');
        Route::post('/', [KeywordController::class, 'store'])->name('keywords.store')->middleware('can:manage-metadata');
        Route::put('{keyword}', [KeywordController::class, 'update'])->name('keywords.update')->middleware('can:manage-metadata');
        Route::delete('{keyword}', [KeywordController::class, 'destroy'])->name('keywords.destroy')->middleware('can:delete-metadata');
        Route::get('export', [KeywordController::class, 'export'])->name('keywords.export')->middleware('can:manage-metadata');
    });

    /*
    |--------------------------------------------------------------------------
    | Letters
    |--------------------------------------------------------------------------
    */
    Route::prefix('letters')->middleware(['auth'])->group(function () {
        Route::get('/', [LetterController::class, 'index'])->name('letters')->middleware('can:view-metadata');
        Route::get('create', [LetterController::class, 'create'])->name('letters.create')->middleware('can:manage-metadata');
        Route::get('{letter}/edit', [LetterController::class, 'edit'])->name('letters.edit')->middleware('can:manage-metadata');
        Route::post('/', [LetterController::class, 'store'])->name('letters.store')->middleware('can:manage-metadata');
        Route::put('{letter}', [LetterController::class, 'update'])->name('letters.update')->middleware('can:manage-metadata');
        Route::delete('{letter}', [LetterController::class, 'destroy'])->name('letters.destroy')->middleware('can:delete-metadata');
        Route::get('export', [LetterController::class, 'export'])->name('letters.export')->middleware('can:manage-metadata');
    });

    /*
    |--------------------------------------------------------------------------
    | AJAX Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('ajax')->middleware(['auth', 'can:manage-metadata'])->group(function () {
        Route::get('professions', [AjaxProfessionController::class, '__invoke'])->name('ajax.professions');
        Route::get('place', [AjaxPlaceController::class, '__invoke'])->name('ajax.places');
        Route::get('identity', [AjaxIdentityController::class, '__invoke'])->name('ajax.identities');
    });
});

require __DIR__ . '/auth.php';
