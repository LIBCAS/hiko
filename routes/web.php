<?php

declare(strict_types=1);

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
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group and tenant-specific middleware.
|
*/

Route::middleware([InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class, 'web'])->group(function () {

    // Root Redirect
    Route::get('/', function () {
        return redirect()->route('letters');
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
        Route::get('/', [LocationController::class, 'index'])
            ->name('locations')
            ->middleware('can:view-metadata');
        Route::get('create', [LocationController::class, 'create'])
            ->name('locations.create')
            ->middleware('can:manage-metadata');
        Route::get('{location}/edit', [LocationController::class, 'edit'])
            ->name('locations.edit')
            ->middleware('can:manage-metadata');
        Route::post('/', [LocationController::class, 'store'])
            ->name('locations.store')
            ->middleware('can:manage-metadata');
        Route::put('{location}', [LocationController::class, 'update'])
            ->name('locations.update')
            ->middleware('can:manage-metadata');
        Route::delete('{location}', [LocationController::class, 'destroy'])
            ->name('locations.destroy')
            ->middleware('can:delete-metadata');
        Route::get('export', [LocationController::class, 'export'])
            ->name('locations.export')
            ->middleware('can:manage-metadata');
    });

    /*
    |--------------------------------------------------------------------------
    | Professions
    |--------------------------------------------------------------------------
    */
    Route::prefix('professions')->middleware(['auth'])->group(function () {
        Route::get('/', [ProfessionController::class, 'index'])
            ->name('professions')
            ->middleware('can:view-metadata');
        Route::get('create', [ProfessionController::class, 'create'])
            ->name('professions.create')
            ->middleware('can:manage-metadata');
        Route::get('{profession}/edit', [ProfessionController::class, 'edit'])
            ->name('professions.edit')
            ->middleware('can:manage-metadata');
        Route::post('/', [ProfessionController::class, 'store'])
            ->name('professions.store')
            ->middleware('can:manage-metadata');
        Route::put('{profession}', [ProfessionController::class, 'update'])
            ->name('professions.update')
            ->middleware('can:manage-metadata');
        Route::delete('{profession}', [ProfessionController::class, 'destroy'])
            ->name('professions.destroy')
            ->middleware('can:delete-metadata');
        Route::get('export', [ProfessionController::class, 'export'])
            ->name('professions.export')
            ->middleware('can:manage-metadata');
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
        Route::get('professions/category/{id}/attach', [ProfessionCategoryController::class, 'attachProfession'])->name('professions.attach');
        Route::post('professions/category/{category}/attach', [ProfessionCategoryController::class, 'storeAttachedProfession'])->name('professions.category.attach');
    });

    /*
    |--------------------------------------------------------------------------
    | Global Professions
    |--------------------------------------------------------------------------
    */
    Route::prefix('global-professions')->middleware(['auth'])->name('global.professions.')->group(function () {
        Route::get('/', [GlobalProfessionController::class, 'index'])
            ->name('index')
            ->middleware('can:view-users');
        Route::get('create', [GlobalProfessionController::class, 'create'])
            ->name('create')
            ->middleware('can:manage-users');
        Route::get('{globalProfession}/edit', [GlobalProfessionController::class, 'edit'])
            ->name('edit')
            ->middleware('can:manage-users');
        Route::post('/', [GlobalProfessionController::class, 'store'])
            ->name('store')
            ->middleware('can:manage-users');
        Route::put('{globalProfession}', [GlobalProfessionController::class, 'update'])
            ->name('update')
            ->middleware('can:manage-users');
        Route::delete('{globalProfession}', [GlobalProfessionController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:delete-users');
    });

    /*
    |--------------------------------------------------------------------------
    | Global Profession Categories
    |--------------------------------------------------------------------------
    */
    Route::prefix('global-profession-categories')->middleware(['auth'])->name('global.profession.category.')->group(function () {
        Route::get('/', [GlobalProfessionCategoryController::class, 'index'])
            ->name('index')
            ->middleware('can:view-users');
        Route::get('create', [GlobalProfessionCategoryController::class, 'create'])
            ->name('create')
            ->middleware('can:manage-users');
        Route::get('{globalProfessionCategory}/edit', [GlobalProfessionCategoryController::class, 'edit'])
            ->name('edit')
            ->middleware('can:manage-users');
        Route::post('/', [GlobalProfessionCategoryController::class, 'store'])
            ->name('store')
            ->middleware('can:manage-users');
        Route::put('{globalProfessionCategory}', [GlobalProfessionCategoryController::class, 'update'])
            ->name('update')
            ->middleware('can:manage-users');
        Route::delete('{globalProfessionCategory}', [GlobalProfessionCategoryController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:delete-users');
    });

    /*
    |--------------------------------------------------------------------------
    | Keywords
    |--------------------------------------------------------------------------
    */
    Route::prefix('keywords')->middleware(['auth'])->group(function () {
        Route::get('/', [KeywordController::class, 'index'])
            ->name('keywords')
            ->middleware('can:view-metadata');
        Route::get('create', [KeywordController::class, 'create'])
            ->name('keywords.create')
            ->middleware('can:manage-metadata');
        Route::get('{keyword}/edit', [KeywordController::class, 'edit'])
            ->name('keywords.edit')
            ->middleware('can:manage-metadata');
        Route::post('/', [KeywordController::class, 'store'])
            ->name('keywords.store')
            ->middleware('can:manage-metadata');
        Route::put('{keyword}', [KeywordController::class, 'update'])
            ->name('keywords.update')
            ->middleware('can:manage-metadata');
        Route::delete('{keyword}', [KeywordController::class, 'destroy'])
            ->name('keywords.destroy')
            ->middleware('can:delete-metadata');
        Route::get('export', [KeywordController::class, 'export'])
            ->name('keywords.export')
            ->middleware('can:manage-metadata');
    });

    /*
    |--------------------------------------------------------------------------
    | Keyword Categories
    |--------------------------------------------------------------------------
    */
    Route::prefix('keywords/category')->middleware(['auth', 'can:manage-metadata'])->group(function () {
        Route::get('/', function () {
            return redirect()->route('keywords');
        });
        Route::get('create', [KeywordCategoryController::class, 'create'])
            ->name('keywords.category.create');
        Route::get('{keywordCategory}/edit', [KeywordCategoryController::class, 'edit'])
            ->name('keywords.category.edit');
        Route::post('/', [KeywordCategoryController::class, 'store'])
            ->name('keywords.category.store');
        Route::put('{keywordCategory}', [KeywordCategoryController::class, 'update'])
            ->name('keywords.category.update');
        Route::delete('{keywordCategory}', [KeywordCategoryController::class, 'destroy'])
            ->name('keywords.category.destroy');
        Route::get('export', [KeywordCategoryController::class, 'export'])
            ->name('keywords.category.export');
    });

    /*
    |--------------------------------------------------------------------------
    | Places
    |--------------------------------------------------------------------------
    */
    Route::prefix('places')->middleware(['auth'])->group(function () {
        Route::get('/', [PlaceController::class, 'index'])
            ->name('places')
            ->middleware('can:view-metadata');
        Route::get('create', [PlaceController::class, 'create'])
            ->name('places.create')
            ->middleware('can:manage-metadata');
        Route::get('{place}/edit', [PlaceController::class, 'edit'])
            ->name('places.edit')
            ->middleware('can:manage-metadata');
        Route::post('/', [PlaceController::class, 'store'])
            ->name('places.store')
            ->middleware('can:manage-metadata');
        Route::put('{place}', [PlaceController::class, 'update'])
            ->name('places.update')
            ->middleware('can:manage-metadata');
        Route::delete('{place}', [PlaceController::class, 'destroy'])
            ->name('places.destroy')
            ->middleware('can:delete-metadata');
        Route::get('export', [PlaceController::class, 'export'])
            ->name('places.export')
            ->middleware('can:manage-metadata');
    });

    /*
    |--------------------------------------------------------------------------
    | Identities
    |--------------------------------------------------------------------------
    */
    Route::prefix('identities')->middleware(['auth'])->group(function () {
        Route::get('/', [IdentityController::class, 'index'])
            ->name('identities')
            ->middleware('can:view-metadata');
        Route::get('create', [IdentityController::class, 'create'])
            ->name('identities.create')
            ->middleware('can:manage-metadata');
        Route::get('{identity}/edit', [IdentityController::class, 'edit'])
            ->name('identities.edit')
            ->middleware('can:manage-metadata');
        Route::post('/', [IdentityController::class, 'store'])
            ->name('identities.store')
            ->middleware('can:manage-metadata');
        Route::put('{identity}', [IdentityController::class, 'update'])
            ->name('identities.update')
            ->middleware('can:manage-metadata');
        Route::delete('{identity}', [IdentityController::class, 'destroy'])
            ->name('identities.destroy')
            ->middleware('can:manage-metadata');
        Route::get('export', [IdentityController::class, 'export'])
            ->name('identities.export')
            ->middleware('can:manage-metadata');
    });

    /*
    |--------------------------------------------------------------------------
    | Letters
    |--------------------------------------------------------------------------
    */
    Route::prefix('letters')->middleware(['auth'])->group(function () {
        Route::get('/', [LetterController::class, 'index'])
            ->name('letters')
            ->middleware('can:view-metadata');
        Route::get('create', [LetterController::class, 'create'])
            ->name('letters.create')
            ->middleware('can:manage-metadata');
        Route::get('{letter}/edit', [LetterController::class, 'edit'])
            ->name('letters.edit')
            ->middleware('can:manage-metadata');
        Route::post('/', [LetterController::class, 'store'])
            ->name('letters.store')
            ->middleware('can:manage-metadata');
        Route::get('{letter}/show', [LetterController::class, 'show'])
            ->name('letters.show')
            ->middleware('can:view-metadata');
        Route::put('{letter}', [LetterController::class, 'update'])
            ->name('letters.update')
            ->middleware('can:manage-metadata');
        Route::delete('{letter}', [LetterController::class, 'destroy'])
            ->name('letters.destroy')
            ->middleware('can:delete-metadata');
        Route::get('export/palladio/character', [LetterController::class, 'exportPalladioCharacter'])
            ->name('letters.export.palladio.character')
            ->middleware('can:manage-metadata');
        Route::get('export', [LetterController::class, 'export'])
            ->name('letters.export')
            ->middleware('can:manage-metadata');
        Route::get('{letter}/images', [LetterController::class, 'images'])
            ->name('letters.images')
            ->middleware('can:manage-metadata');
        Route::get('{letter}/text', [LetterController::class, 'text'])
            ->name('letters.text')
            ->middleware('can:manage-metadata');
        Route::get('preview', [LetterPreviewController::class, '__invoke'])
            ->name('letters.preview')
            ->middleware('can:view-metadata');
        Route::get('{letter}/duplicate', [LetterController::class, 'duplicate'])
            ->name('letters.duplicate')
            ->middleware('can:manage-metadata');
    });

    /*
    |--------------------------------------------------------------------------
    | Compare Letters
    |--------------------------------------------------------------------------
    */
    Route::prefix('compare-letters')->middleware(['auth'])->group(function () {
        Route::get('/', [LetterComparisonController::class, 'index'])
            ->name('compare-letters.index')
            ->middleware('can:view-metadata');
        Route::post('search', [LetterComparisonController::class, 'search'])
            ->name('compare-letters.search')
            ->middleware('can:view-metadata');
        Route::get('{comparison}/show', [LetterComparisonController::class, 'show'])
            ->name('compare-letters.show')
            ->middleware('can:view-metadata');
        Route::post('ajax/compare', [AjaxLetterComparisonController::class, 'search'])
            ->name('compare-letters.ajax')
            ->middleware('can:view-metadata');
    });

    /*
    |--------------------------------------------------------------------------
    | AJAX Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('ajax')->middleware(['auth', 'can:manage-metadata'])->group(function () {
        Route::get('keyword-category', [AjaxKeywordCategoryController::class, '__invoke'])
            ->name('ajax.keywords.category');
        Route::get('professions', [AjaxProfessionController::class, '__invoke'])
            ->name('ajax.professions');
        Route::get('profession-category', [AjaxProfessionCategoryController::class, '__invoke'])
            ->name('ajax.professions.category');
        Route::get('global-profession-category', [AjaxGlobalProfessionCategoryController::class, '__invoke'])
            ->name('ajax.global.professions.category');
        Route::get('identity', [AjaxIdentityController::class, '__invoke'])
            ->name('ajax.identities');
        Route::get('identity/similar', [SimilarNamesController::class, '__invoke'])
            ->name('ajax.identities.similar');
        Route::get('place', [AjaxPlaceController::class, '__invoke'])
            ->name('ajax.places');
        Route::get('places/similar', [SimilarPlacesController::class, '__invoke'])
            ->name('ajax.places.similar');
        Route::get('keyword', [AjaxKeywordController::class, '__invoke'])
            ->name('ajax.keywords');
        Route::get('locations/similar', [SimilarLocationsController::class, '__invoke'])
            ->name('ajax.locations.similar');
        Route::get('items/similar', [SimilarItemsController::class, '__invoke'])
            ->name('ajax.items.similar');
    });

    /*
    |--------------------------------------------------------------------------
    | Dev Tools
    |--------------------------------------------------------------------------
    */
    Route::prefix('dev')->middleware(['auth', 'can:debug'])->group(function () {
        Route::get('optimize', [DevToolsController::class, 'cache'])->name('dev.optimize');
        Route::get('clear', [DevToolsController::class, 'clear'])->name('dev.clear');
        Route::get('flush-index', [DevToolsController::class, 'flushSearchIndex'])->name('dev.flush-index');
        Route::get('build-index', [DevToolsController::class, 'buildSearchIndex'])->name('dev.build-index');
        Route::get('symlink', [DevToolsController::class, 'symlink'])->name('dev.symlink');
    });

    /*
    |--------------------------------------------------------------------------
    | Miscellaneous Routes
    |--------------------------------------------------------------------------
    */
    Route::get('edit/{letter:uuid}', [EditLinkController::class, '__invoke'])
        ->name('edit-link')
        ->middleware(['auth']);

    Route::get('image/{letter:uuid}/{imageId}', [ImageShowController::class, '__invoke'])
        ->name('image')
        ->middleware(['auth']);

    Route::get('account', [AccountController::class, '__invoke'])
        ->name('account')
        ->middleware(['auth']);

    Route::post('merge', [MergeController::class, '__invoke'])
        ->name('merge')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('lang/{lang}', [LanguageController::class, '__invoke'])
        ->name('lang');

    /*
    |--------------------------------------------------------------------------
    | Images
    |--------------------------------------------------------------------------
    */
    Route::prefix('images')->middleware(['auth'])->group(function () {
        Route::get('/upload', [ImageController::class, 'showUploadForm'])->name('images.upload');
        Route::post('/upload', [ImageController::class, 'uploadImage'])->name('images.upload.post');
        Route::get('/annotate/{image}', [ImageController::class, 'annotateImage'])->name('images.annotate');
        Route::post('/extract-text', [ImageController::class, 'extractText'])->name('images.extractText');
    });

    Route::get('/images/{letterUuid}/{imageId}', [ImageShowController::class, '__invoke'])
        ->name('images.show')
        ->middleware(['auth']);
});

require __DIR__ . '/auth.php';
