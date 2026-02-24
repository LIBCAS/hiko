<?php

namespace Tests\Unit;

use App\Http\Requests\GlobalPlaceMergeRequest;
use App\Http\Requests\GlobalPlaceRequest;
use PHPUnit\Framework\TestCase;

class AuthorizationRulesTest extends TestCase
{
    public function test_global_place_requests_require_manage_users_ability(): void
    {
        $authorizedUser = new class {
            public function hasAbility(string $ability): bool
            {
                return $ability === 'manage-users';
            }
        };

        $placeRequest = GlobalPlaceRequest::create('/api/v2/global-places', 'POST');
        $placeRequest->setUserResolver(fn() => $authorizedUser);

        $mergeRequest = GlobalPlaceMergeRequest::create('/places/global-merge', 'POST');
        $mergeRequest->setUserResolver(fn() => $authorizedUser);

        $this->assertTrue($placeRequest->authorize());
        $this->assertTrue($mergeRequest->authorize());
    }

    public function test_global_place_requests_reject_users_without_manage_users_ability(): void
    {
        $unauthorizedUser = new class {
            public function hasAbility(string $ability): bool
            {
                return false;
            }
        };

        $placeRequest = GlobalPlaceRequest::create('/api/v2/global-places', 'POST');
        $placeRequest->setUserResolver(fn() => $unauthorizedUser);

        $mergeRequest = GlobalPlaceMergeRequest::create('/places/global-merge', 'POST');
        $mergeRequest->setUserResolver(fn() => $unauthorizedUser);

        $this->assertFalse($placeRequest->authorize());
        $this->assertFalse($mergeRequest->authorize());
    }

    public function test_web_routes_do_not_reference_nonexistent_view_users_ability(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/routes/web.php');

        $this->assertIsString($contents);
        $this->assertStringNotContainsString('can:view-users', $contents);
        $this->assertStringContainsString("Route::get('/', [GlobalProfessionController::class, 'index'])", $contents);
        $this->assertStringContainsString("Route::get('/', [GlobalProfessionCategoryController::class, 'index'])", $contents);
        $this->assertStringContainsString("Route::get('/', [GlobalKeywordController::class, 'index'])", $contents);
        $this->assertStringContainsString("Route::get('/', [GlobalKeywordCategoryController::class, 'index'])", $contents);
        $this->assertStringContainsString("->middleware('can:view-metadata');", $contents);
    }

    public function test_global_api_write_routes_are_guarded_by_manage_users_ability(): void
    {
        $contents = file_get_contents(dirname(__DIR__, 2) . '/routes/api.php');

        $this->assertIsString($contents);
        $this->assertStringContainsString("Route::post('global-places', [apiV2GlobalPlaceController::class, 'store'])->middleware('can:manage-users');", $contents);
        $this->assertStringContainsString("Route::put('global-place/{id}', [apiV2GlobalPlaceController::class, 'update'])->middleware('can:manage-users');", $contents);
        $this->assertStringContainsString("Route::post('global-professions', [apiV2GlobalProfessionController::class, 'store'])->middleware('can:manage-users');", $contents);
        $this->assertStringContainsString("Route::put('global-profession/{id}', [apiV2GlobalProfessionController::class, 'update'])->middleware('can:manage-users');", $contents);
        $this->assertStringContainsString("Route::post('global-keywords', [apiV2GlobalKeywordController::class, 'store'])->middleware('can:manage-users');", $contents);
        $this->assertStringContainsString("Route::put('global-keyword/{id}', [apiV2GlobalKeywordController::class, 'update'])->middleware('can:manage-users');", $contents);
    }
}
