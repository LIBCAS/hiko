<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\MergeController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\LetterController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\KeywordController;
use App\Http\Controllers\DevToolsController;
use App\Http\Controllers\EditLinkController;
use App\Http\Controllers\IdentityController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfessionController;
use App\Http\Controllers\ProfessionCategoryController;
use App\Http\Controllers\GlobalProfessionController;
use App\Http\Controllers\GlobalProfessionCategoryController;
use App\Http\Controllers\LetterPreviewController;
use App\Http\Controllers\KeywordCategoryController;
use App\Http\Controllers\GlobalKeywordController;
use App\Http\Controllers\GlobalKeywordCategoryController;
use App\Http\Controllers\LetterComparisonController;
use App\Http\Controllers\TenantStorageController;
use App\Http\Controllers\OAIPMHController;
use App\Http\Controllers\MergeLetterController;
use App\Http\Controllers\Ajax\AjaxPlaceController;
use App\Http\Controllers\Ajax\AjaxKeywordController;
use App\Http\Controllers\Ajax\AjaxIdentityController;
use App\Http\Controllers\Ajax\SimilarItemsController;
use App\Http\Controllers\Ajax\SimilarNamesController;
use App\Http\Controllers\Ajax\SimilarPlacesController;
use App\Http\Controllers\Ajax\AjaxProfessionController;
use App\Http\Controllers\Ajax\SimilarLocationsController;
use App\Http\Controllers\Ajax\AjaxKeywordCategoryController;
use App\Http\Controllers\Ajax\AjaxGlobalKeywordCategoryController;
use App\Http\Controllers\Ajax\AjaxProfessionCategoryController;
use App\Http\Controllers\Ajax\AjaxGlobalProfessionCategoryController;
use App\Http\Controllers\Ajax\AjaxLetterComparisonController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
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

Route::middleware([InitializeTenancyByDomain::class],'web')->group(function () {
    Route::get('/', function () {
        return redirect()->route('letters');
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->name('users')
            ->middleware(['auth', 'can:manage-users']);

        Route::get('create', [UserController::class, 'create'])
            ->name('users.create')
            ->middleware(['auth', 'can:manage-users']);

        Route::get('{user}/edit', [UserController::class, 'edit'])
            ->name('users.edit')
            ->middleware(['auth', 'can:manage-users']);

        Route::post('/', [UserController::class, 'store'])
            ->name('users.store')
            ->middleware(['auth', 'can:manage-users']);

        Route::put('{user}', [UserController::class, 'update'])
            ->name('users.update')
            ->middleware(['auth', 'can:manage-users']);

        Route::delete('{user}', [UserController::class, 'destroy'])
            ->name('users.destroy')
            ->middleware(['auth', 'can:manage-users']);
    });

    Route::prefix('locations')->group(function () {
        Route::get('/', [LocationController::class, 'index'])
            ->name('locations')
            ->middleware(['auth', 'can:view-metadata']);

        Route::get('create', [LocationController::class, 'create'])
            ->name('locations.create')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('{location}/edit', [LocationController::class, 'edit'])
            ->name('locations.edit')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::post('/', [LocationController::class, 'store'])
            ->name('locations.store')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::put('{location}', [LocationController::class, 'update'])
            ->name('locations.update')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::delete('{location}', [LocationController::class, 'destroy'])
            ->name('locations.destroy')
            ->middleware(['auth', 'can:delete-metadata']);

        Route::get('export', [LocationController::class, 'export'])
            ->name('locations.export')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('/repository/search', [LocationController::class, 'searchRepository'])
            ->name('locations.repository.search')
            ->middleware(['auth', 'can:view-metadata']);

        Route::get('/archive/search', [LocationController::class, 'searchArchive'])
            ->name('locations.archive.search')
            ->middleware(['auth', 'can:view-metadata']);
            
        Route::get('/collection/search', [LocationController::class, 'searchCollection'])
            ->name('locations.collection.search')
        ->middleware(['auth', 'can:view-metadata']);
    });

    Route::prefix('professions')->group(function () {
        Route::get('/', [ProfessionController::class, 'index'])
            ->name('professions')
            ->middleware(['auth', 'can:view-metadata']);

        Route::get('create', [ProfessionController::class, 'create'])
            ->name('professions.create')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('{profession}/edit', [ProfessionController::class, 'edit'])
            ->name('professions.edit')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::post('/', [ProfessionController::class, 'store'])
            ->name('professions.store')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::put('{profession}', [ProfessionController::class, 'update'])
            ->name('professions.update')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::delete('{profession}', [ProfessionController::class, 'destroy'])
            ->name('professions.destroy')
            ->middleware(['auth', 'can:delete-metadata']);

        Route::get('export', [ProfessionController::class, 'export'])
            ->name('professions.export')
            ->middleware(['auth', 'can:manage-metadata']);
    });

    Route::prefix('professions/category')->group(function () {
        Route::get('/', [ProfessionCategoryController::class, 'index'])
            ->name('professions.category')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('create', [ProfessionCategoryController::class, 'create'])
            ->name('professions.category.create')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('{professionCategory}/edit', [ProfessionCategoryController::class, 'edit'])
            ->name('professions.category.edit')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::post('/', [ProfessionCategoryController::class, 'store'])
            ->name('professions.category.store')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::put('{professionCategory}', [ProfessionCategoryController::class, 'update'])
            ->name('professions.category.update')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::delete('{professionCategory}', [ProfessionCategoryController::class, 'destroy'])
            ->name('professions.category.destroy')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('export', [ProfessionCategoryController::class, 'export'])
            ->name('professions.category.export')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('professions/category/{id}/attach', [ProfessionCategoryController::class, 'attachProfession'])
            ->name('professions.attach')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::post('professions/category/{category}/attach', [ProfessionCategoryController::class, 'storeAttachedProfession'])
            ->name('professions.category.attach')
            ->middleware(['auth', 'can:manage-metadata']);
    });

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
            ->middleware('can:manage-users');
    });
    
    Route::prefix('global-profession-categories')->middleware(['auth'])->name('global.professions.category.')->group(function () {
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
            ->middleware('can:manage-users');
    });
    
    Route::prefix('keywords')->group(function () {
        Route::get('/', [KeywordController::class, 'index'])
            ->name('keywords')
            ->middleware(['auth', 'can:view-metadata']);

        Route::get('create', [KeywordController::class, 'create'])
            ->name('keywords.create')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('{keyword}/edit', [KeywordController::class, 'edit'])
            ->name('keywords.edit')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::post('/', [KeywordController::class, 'store'])
            ->name('keywords.store')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::put('{keyword}', [KeywordController::class, 'update'])
            ->name('keywords.update')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::delete('{keyword}', [KeywordController::class, 'destroy'])
            ->name('keywords.destroy')
            ->middleware(['auth', 'can:delete-metadata']);

        Route::get('export', [KeywordController::class, 'export'])
            ->name('keywords.export')
            ->middleware(['auth', 'can:manage-metadata']);
    });

    Route::prefix('keywords/category')->group(function () {
        Route::get('/', function () {
            return redirect()->route('keywords');
        });

        Route::get('create', [KeywordCategoryController::class, 'create'])
            ->name('keywords.category.create')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('{keywordCategory}/edit', [KeywordCategoryController::class, 'edit'])
            ->name('keywords.category.edit')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::post('/', [KeywordCategoryController::class, 'store'])
            ->name('keywords.category.store')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::put('{keywordCategory}', [KeywordCategoryController::class, 'update'])
            ->name('keywords.category.update')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::delete('{keywordCategory}', [KeywordCategoryController::class, 'destroy'])
            ->name('keywords.category.destroy')
            ->middleware(['auth', 'can:delete-metadata']);

        Route::get('export', [KeywordCategoryController::class, 'export'])
            ->name('keywords.category.export')
            ->middleware(['auth', 'can:manage-metadata']);
    });

    Route::prefix('global-keywords')->middleware(['auth'])->name('global.keywords.')->group(function () {
        Route::get('/', [GlobalKeywordController::class, 'index'])
            ->name('index')
            ->middleware('can:view-users');
    
        Route::get('create', [GlobalKeywordController::class, 'create'])
            ->name('create')
            ->middleware('can:manage-users');

        Route::get('{globalKeyword}/edit', [GlobalKeywordController::class, 'edit'])
            ->name('edit')
            ->middleware('can:manage-users');

        Route::post('/', [GlobalKeywordController::class, 'store'])
            ->name('store')
            ->middleware('can:manage-users');

        Route::put('{globalKeyword}', [GlobalKeywordController::class, 'update'])
            ->name('update')
            ->middleware('can:manage-users');

        Route::delete('{globalKeyword}', [GlobalKeywordController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:manage-users');
    });
    
    Route::prefix('global-keyword-categories')->middleware(['auth'])->name('global.keywords.category.')->group(function () {
        Route::get('/', [GlobalKeywordCategoryController::class, 'index'])
            ->name('index')
            ->middleware('can:view-users');
    
        Route::get('create', [GlobalKeywordCategoryController::class, 'create'])
            ->name('create')
            ->middleware('can:manage-users');

        Route::get('{globalKeywordCategory}/edit', [GlobalKeywordCategoryController::class, 'edit'])
            ->name('edit')
            ->middleware('can:manage-users');

        Route::post('/', [GlobalKeywordCategoryController::class, 'store'])
            ->name('store')
            ->middleware('can:manage-users');

        Route::put('{globalKeywordCategory}', [GlobalKeywordCategoryController::class, 'update'])
            ->name('update')
            ->middleware('can:manage-users');

        Route::delete('{globalKeywordCategory}', [GlobalKeywordCategoryController::class, 'destroy'])
            ->name('destroy')
            ->middleware('can:manage-users');
    });

    Route::prefix('places')->group(function () {
        Route::get('/', [PlaceController::class, 'index'])
            ->name('places')
            ->middleware(['auth', 'can:view-metadata']);

        Route::get('create', [PlaceController::class, 'create'])
            ->name('places.create')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('{place}/edit', [PlaceController::class, 'edit'])
            ->name('places.edit')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::post('/', [PlaceController::class, 'store'])
            ->name('places.store')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::put('{place}', [PlaceController::class, 'update'])
            ->name('places.update')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::delete('{place}', [PlaceController::class, 'destroy'])
            ->name('places.destroy')
            ->middleware(['auth', 'can:delete-metadata']);

        Route::get('export', [PlaceController::class, 'export'])
            ->name('places.export')
            ->middleware(['auth', 'can:manage-metadata']);
    });

    Route::prefix('identities')->group(function () {
        Route::get('/', [IdentityController::class, 'index'])
            ->name('identities')
            ->middleware(['auth', 'can:view-metadata']);

        Route::get('create', [IdentityController::class, 'create'])
            ->name('identities.create')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('{identity}/edit', [IdentityController::class, 'edit'])
            ->name('identities.edit')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::post('/', [IdentityController::class, 'store'])
            ->name('identities.store')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::put('{identity}', [IdentityController::class, 'update'])
            ->name('identities.update')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::delete('{identity}', [IdentityController::class, 'destroy'])
            ->name('identities.destroy')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('export', [IdentityController::class, 'export'])
            ->name('identities.export')
            ->middleware(['auth', 'can:manage-metadata']);
    });

    Route::prefix('letters')->group(function () {
        Route::get('/merge', [MergeLetterController::class, 'mergeForm'])
            ->name('letters.merge.form')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::post('/merge', [MergeLetterController::class, 'merge'])
            ->name('letters.merge')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('/', [LetterController::class, 'index'])
            ->name('letters')
            ->middleware(['auth', 'can:view-metadata']);
    
        Route::get('create', [LetterController::class, 'create'])
            ->name('letters.create')
            ->middleware(['auth', 'can:manage-metadata']);
        
        Route::get('{letter}/edit', [LetterController::class, 'edit'])
            ->name('letters.edit')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::post('/', [LetterController::class, 'store'])
            ->name('letters.store')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('{letter}/show', [LetterController::class, 'show'])
            ->name('letters.show')
            ->middleware(['auth', 'can:view-metadata']);
    
        Route::put('{letter}', [LetterController::class, 'update'])
            ->name('letters.update')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::delete('{letter}', [LetterController::class, 'destroy'])
            ->name('letters.destroy')
            ->middleware(['auth', 'can:delete-metadata']);
    
        Route::get('export/palladio/character', [LetterController::class, 'exportPalladioCharacter'])
            ->name('letters.export.palladio.character')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('export', [LetterController::class, 'export'])
            ->name('letters.export')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('{letter}/images', [LetterController::class, 'images'])
            ->name('letters.images')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('{letter}/text', [LetterController::class, 'text'])
            ->name('letters.text')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('preview', LetterPreviewController::class)
            ->name('letters.preview')
            ->middleware(['auth', 'can:view-metadata']);
    
        Route::get('{letter}/duplicate', [LetterController::class, 'duplicate'])
            ->name('letters.duplicate')
            ->middleware(['auth', 'can:manage-metadata']);
    });
    
    Route::prefix('compare-letters')->group(function () {
        Route::get('/', [LetterComparisonController::class, 'index'])
            ->name('compare-letters.index')
            ->middleware(['auth', 'can:view-metadata']);
    
        Route::post('search', [LetterComparisonController::class, 'search'])
            ->name('compare-letters.search')
            ->middleware(['auth', 'can:view-metadata']);
            
        Route::get('{comparison}/show', [LetterComparisonController::class, 'show'])
            ->name('compare-letters.show')
            ->middleware(['auth', 'can:view-metadata']);
    
        Route::post('ajax/compare', [AjaxLetterComparisonController::class, 'search'])
            ->name('compare-letters.ajax')
            ->middleware(['auth', 'can:view-metadata']);
    });    
    
    Route::prefix('ajax')->group(function () {
        Route::get('keyword-category', [AjaxKeywordCategoryController::class, '__invoke'])
            ->name('ajax.keywords.category')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('professions', [AjaxProfessionController::class, '__invoke'])
            ->name('ajax.professions')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('profession-category', [AjaxProfessionCategoryController::class, '__invoke'])
            ->name('ajax.professions.category')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('global-profession-category', [AjaxGlobalProfessionCategoryController::class, '__invoke'])
            ->name('ajax.global.professions.category')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('identity', [AjaxIdentityController::class, '__invoke'])
            ->name('ajax.identities')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('identity/similar', [SimilarNamesController::class, '__invoke'])
            ->name('ajax.identities.similar')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('place', [AjaxPlaceController::class, '__invoke'])
            ->name('ajax.places')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('places/similar', [SimilarPlacesController::class, '__invoke'])
            ->name('ajax.places.similar')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('keyword', [AjaxKeywordController::class, '__invoke'])
            ->name('ajax.keywords')
            ->middleware(['auth', 'can:manage-metadata']);

        Route::get('global-keyword-category', [AjaxGlobalKeywordCategoryController::class, '__invoke'])
            ->name('ajax.global.keywords.category')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('locations/similar', [SimilarLocationsController::class, '__invoke'])
            ->name('ajax.locations.similar')
            ->middleware(['auth', 'can:manage-metadata']);
    
        Route::get('items/similar', [SimilarItemsController::class, '__invoke'])
            ->name('ajax.items.similar')
            ->middleware(['auth', 'can:manage-metadata']);
    });    

    Route::prefix('dev')->group(function () {
        Route::get('optimize', [DevToolsController::class, 'cache'])
            ->middleware(['auth', 'can:debug']);

        Route::get('clear', [DevToolsController::class, 'clear'])
            ->middleware(['auth', 'can:debug']);

        Route::get('flush-index', [DevToolsController::class, 'flushSearchIndex'])
            ->middleware(['auth', 'can:debug']);

        Route::get('build-index', [DevToolsController::class, 'buildSearchIndex'])
            ->middleware(['auth', 'can:debug']);

        Route::get('symlink', [DevToolsController::class, 'symlink'])
            ->middleware(['auth', 'can:debug']);
    });

    Route::get('/tenant-storage/{path}', [TenantStorageController::class, 'show'])
        ->where('path', '.*')
        ->name('tenant.storage')
        ->middleware(['auth']);  

    Route::get('edit/{letter:uuid}', EditLinkController::class)
        ->name('edit-link')
        ->middleware(['auth']);

    Route::get('image/{letter:uuid}/{imageId}', ImageController::class)
        ->name('image');

    Route::get('account', AccountController::class)
        ->name('account')
        ->middleware(['auth']);

    Route::post('merge', MergeController::class)
        ->name('merge')
        ->middleware(['auth', 'can:manage-metadata']);

    Route::get('lang/{lang}', LanguageController::class)
        ->name('lang');
        Route::get('/serve-local-file/{path}', function ($path) {
            $path = urldecode($path);
        
            // Prevent directory traversal attacks
            if (strpos($path, '..') !== false || strpos($path, '/') === 0) {
                abort(403);
            }
        
            if (!Storage::disk('local')->exists($path)) {
                abort(404);
            }
        
            $file = Storage::disk('local')->get($path);
            $type = Storage::disk('local')->mimeType($path);
        
            return response($file, 200)->header('Content-Type', $type);
        })->name('serve-local-file')->where('path', '.*');

    Route::get('/oai-pmh', [OAIPMHController::class, 'handle'])->name('oai-pmh');
});

require __DIR__ . '/auth.php';
