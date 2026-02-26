<?php

namespace Tests\Feature\Api\V2;

use Tests\TestCase;

class CreateEndpointsContractTest extends TestCase
{
    public function test_v2_create_endpoints_use_created_status_and_id_in_resource_payload(): void
    {
        $routesApi = file_get_contents(base_path('routes/api.php'));

        $contracts = [
            [
                'route' => "Route::post('letters', [apiV2LetterController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/LetterController.php',
                'resource' => 'app/Http/Resources/LetterResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 2,
            ],
            [
                'route' => "Route::post('locations', [apiV2LocationController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/LocationController.php',
                'resource' => 'app/Http/Resources/LocationResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-places', [apiV2GlobalPlaceController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalPlaceController.php',
                'resource' => 'app/Http/Resources/PlaceResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('places', [apiV2PlaceController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/PlaceController.php',
                'resource' => 'app/Http/Resources/PlaceResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('identities', [apiV2IdentityController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/IdentityController.php',
                'resource' => 'app/Http/Resources/IdentityResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-identities', [apiV2GlobalIdentityController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalIdentityController.php',
                'resource' => 'app/Http/Resources/IdentityResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-profession-categories', [apiV2GlobalProfessionCategoryController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalProfessionCategoryController.php',
                'resource' => 'app/Http/Resources/ProfessionCategoryResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-professions', [apiV2GlobalProfessionController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalProfessionController.php',
                'resource' => 'app/Http/Resources/ProfessionResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('profession-categories', [apiV2ProfessionCategoryController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/ProfessionCategoryController.php',
                'resource' => 'app/Http/Resources/ProfessionCategoryResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('professions', [apiV2ProfessionController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/ProfessionController.php',
                'resource' => 'app/Http/Resources/ProfessionResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-keyword-categories', [apiV2GlobalKeywordCategoryController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalKeywordCategoryController.php',
                'resource' => 'app/Http/Resources/KeywordCategoryResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-keywords', [apiV2GlobalKeywordController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalKeywordController.php',
                'resource' => 'app/Http/Resources/KeywordResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('keyword-categories', [apiV2KeywordCategoryController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/KeywordCategoryController.php',
                'resource' => 'app/Http/Resources/KeywordCategoryResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('keywords', [apiV2KeywordController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/KeywordController.php',
                'resource' => 'app/Http/Resources/KeywordResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' => \$this->id,",
                'minIdNeedleCount' => 1,
            ],
        ];

        foreach ($contracts as $contract) {
            $controllerCode = file_get_contents(base_path($contract['controller']));
            $resourceCode = file_get_contents(base_path($contract['resource']));

            $this->assertIsString($controllerCode);
            $this->assertIsString($resourceCode);
            $this->assertIsString($routesApi);

            $this->assertStringContainsString($contract['route'], $routesApi);
            $this->assertStringContainsString('public function store(', $controllerCode);
            $this->assertStringContainsString($contract['statusNeedle'], $controllerCode);

            $this->assertGreaterThanOrEqual(
                $contract['minIdNeedleCount'],
                substr_count($resourceCode, $contract['idNeedle'])
            );
        }
    }
}
