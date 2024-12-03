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
use App\Http\Controllers\LetterComparisonController;
use App\Http\Controllers\TenantStorageController;
use App\Http\Controllers\FileController;
use Google\Cloud\DocumentAI\V1\Client\DocumentProcessorServiceClient;
use App\Http\Controllers\Ajax\AjaxPlaceController;
use App\Http\Controllers\Ajax\AjaxKeywordController;
use App\Http\Controllers\Ajax\AjaxIdentityController;
use App\Http\Controllers\Ajax\SimilarItemsController;
use App\Http\Controllers\Ajax\SimilarNamesController;
use App\Http\Controllers\Ajax\SimilarPlacesController;
use App\Http\Controllers\Ajax\AjaxProfessionController;
use App\Http\Controllers\Ajax\SimilarLocationsController;
use App\Http\Controllers\Ajax\AjaxKeywordCategoryController;
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

Route::middleware([InitializeTenancyByDomain::class, 'web'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('letters');
    });

    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->name('users')
            ->middleware(['can:manage-users']);

        Route::get('create', [UserController::class, 'create'])
            ->name('users.create')
            ->middleware(['can:manage-users']);

        Route::get('{user}/edit', [UserController::class, 'edit'])
            ->name('users.edit')
            ->middleware(['can:manage-users']);

        Route::post('/', [UserController::class, 'store'])
            ->name('users.store')
            ->middleware(['can:manage-users']);

        Route::put('{user}', [UserController::class, 'update'])
            ->name('users.update')
            ->middleware(['can:manage-users']);

        Route::delete('{user}', [UserController::class, 'destroy'])
            ->name('users.destroy')
            ->middleware(['can:manage-users']);
    });

    Route::prefix('locations')->group(function () {
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

    Route::prefix('professions')->group(function () {
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

    Route::prefix('professions/category')->group(function () {
        Route::get('/', [ProfessionCategoryController::class, 'index'])
            ->name('professions.category')
            ->middleware('can:manage-metadata');

        Route::get('create', [ProfessionCategoryController::class, 'create'])
            ->name('professions.category.create')
            ->middleware('can:manage-metadata');

        Route::get('{professionCategory}/edit', [ProfessionCategoryController::class, 'edit'])
            ->name('professions.category.edit')
            ->middleware('can:manage-metadata');

        Route::post('/', [ProfessionCategoryController::class, 'store'])
            ->name('professions.category.store')
            ->middleware('can:manage-metadata');

        Route::put('{professionCategory}', [ProfessionCategoryController::class, 'update'])
            ->name('professions.category.update')
            ->middleware('can:manage-metadata');

        Route::delete('{professionCategory}', [ProfessionCategoryController::class, 'destroy'])
            ->name('professions.category.destroy')
            ->middleware('can:manage-metadata');

        Route::get('export', [ProfessionCategoryController::class, 'export'])
            ->name('professions.category.export')
            ->middleware('can:manage-metadata');

        Route::get('professions/category/{id}/attach', [ProfessionCategoryController::class, 'attachProfession'])
            ->name('professions.attach')
            ->middleware('can:manage-metadata');

        Route::post('professions/category/{category}/attach', [ProfessionCategoryController::class, 'storeAttachedProfession'])
            ->name('professions.category.attach')
            ->middleware('can:manage-metadata');
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

    Route::prefix('keywords/category')->group(function () {
        Route::get('/', function () {
            return redirect()->route('keywords');
        });

        Route::get('create', [KeywordCategoryController::class, 'create'])
            ->name('keywords.category.create')
            ->middleware('can:manage-metadata');

        Route::get('{keywordCategory}/edit', [KeywordCategoryController::class, 'edit'])
            ->name('keywords.category.edit')
            ->middleware('can:manage-metadata');

        Route::post('/', [KeywordCategoryController::class, 'store'])
            ->name('keywords.category.store')
            ->middleware('can:manage-metadata');

        Route::put('{keywordCategory}', [KeywordCategoryController::class, 'update'])
            ->name('keywords.category.update')
            ->middleware('can:manage-metadata');

        Route::delete('{keywordCategory}', [KeywordCategoryController::class, 'destroy'])
            ->name('keywords.category.destroy')
            ->middleware('can:delete-metadata');

        Route::get('export', [KeywordCategoryController::class, 'export'])
            ->name('keywords.category.export')
            ->middleware('can:manage-metadata');
    });

    Route::prefix('places')->group(function () {
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

    Route::prefix('identities')->group(function () {
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
            ->middleware('can:delete-metadata');

        Route::get('export', [IdentityController::class, 'export'])
            ->name('identities.export')
            ->middleware('can:manage-metadata');
    });

    Route::prefix('letters')->group(function () {
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
    
        Route::get('preview', LetterPreviewController::class)
            ->name('letters.preview')
            ->middleware('can:view-metadata');
    
        Route::get('{letter}/duplicate', [LetterController::class, 'duplicate'])
            ->name('letters.duplicate')
            ->middleware('can:manage-metadata');
    });
    
    Route::prefix('compare-letters')->group(function () {
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
    
    Route::prefix('ajax')->group(function () {
        Route::get('keyword-category', [AjaxKeywordCategoryController::class, '__invoke'])
            ->name('ajax.keywords.category')
            ->middleware('can:manage-metadata');
    
        Route::get('professions', [AjaxProfessionController::class, '__invoke'])
            ->name('ajax.professions')
            ->middleware('can:manage-metadata');
    
        Route::get('profession-category', [AjaxProfessionCategoryController::class, '__invoke'])
            ->name('ajax.professions.category')
            ->middleware('can:manage-metadata');
    
        Route::get('global-profession-category', [AjaxGlobalProfessionCategoryController::class, '__invoke'])
            ->name('ajax.global.professions.category')
            ->middleware('can:manage-metadata');
    
        Route::get('identity', [AjaxIdentityController::class, '__invoke'])
            ->name('ajax.identities')
            ->middleware('can:manage-metadata');
    
        Route::get('identity/similar', [SimilarNamesController::class, '__invoke'])
            ->name('ajax.identities.similar')
            ->middleware('can:manage-metadata');
    
        Route::get('place', [AjaxPlaceController::class, '__invoke'])
            ->name('ajax.places')
            ->middleware('can:manage-metadata');
    
        Route::get('places/similar', [SimilarPlacesController::class, '__invoke'])
            ->name('ajax.places.similar')
            ->middleware('can:manage-metadata');
    
        Route::get('keyword', [AjaxKeywordController::class, '__invoke'])
            ->name('ajax.keywords')
            ->middleware('can:manage-metadata');
    
        Route::get('locations/similar', [SimilarLocationsController::class, '__invoke'])
            ->name('ajax.locations.similar')
            ->middleware('can:manage-metadata');
    
        Route::get('items/similar', [SimilarItemsController::class, '__invoke'])
            ->name('ajax.items.similar')
            ->middleware('can:manage-metadata');
    });    

    Route::prefix('dev')->group(function () {
        Route::get('optimize', [DevToolsController::class, 'cache'])
            ->middleware(['can:debug']);

        Route::get('clear', [DevToolsController::class, 'clear'])
            ->middleware(['can:debug']);

        Route::get('flush-index', [DevToolsController::class, 'flushSearchIndex'])
            ->middleware(['can:debug']);

        Route::get('build-index', [DevToolsController::class, 'buildSearchIndex'])
            ->middleware(['can:debug']);

        Route::get('symlink', [DevToolsController::class, 'symlink'])
            ->middleware(['can:debug']);
    });

    Route::get('/tenant-storage/{path}', [TenantStorageController::class, 'show'])
        ->where('path', '.*')
        ->name('tenant.storage')
        ->middleware('auth');  

    Route::get('edit/{letter:uuid}', EditLinkController::class)
        ->name('edit-link')
        ->middleware('auth');

    Route::get('image/{letter:uuid}/{imageId}', ImageController::class)
        ->name('image');

    Route::get('account', AccountController::class)
        ->name('account')
        ->middleware('auth');

    Route::post('merge', MergeController::class)
        ->name('merge')
        ->middleware('can:manage-metadata');

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
});

require __DIR__ . '/auth.php';
