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
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 2,
            ],
            [
                'route' => "Route::post('locations', [apiV2LocationController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/LocationController.php',
                'resource' => 'app/Http/Resources/LocationResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-places', [apiV2GlobalPlaceController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalPlaceController.php',
                'resource' => 'app/Http/Resources/PlaceResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-locations', [apiV2GlobalLocationController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalLocationController.php',
                'resource' => 'app/Http/Resources/LocationResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('places', [apiV2PlaceController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/PlaceController.php',
                'resource' => 'app/Http/Resources/PlaceResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('identities', [apiV2IdentityController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/IdentityController.php',
                'resource' => 'app/Http/Resources/IdentityResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-identities', [apiV2GlobalIdentityController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalIdentityController.php',
                'resource' => 'app/Http/Resources/IdentityResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-profession-categories', [apiV2GlobalProfessionCategoryController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalProfessionCategoryController.php',
                'resource' => 'app/Http/Resources/ProfessionCategoryResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-professions', [apiV2GlobalProfessionController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalProfessionController.php',
                'resource' => 'app/Http/Resources/ProfessionResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('profession-categories', [apiV2ProfessionCategoryController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/ProfessionCategoryController.php',
                'resource' => 'app/Http/Resources/ProfessionCategoryResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('professions', [apiV2ProfessionController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/ProfessionController.php',
                'resource' => 'app/Http/Resources/ProfessionResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-keyword-categories', [apiV2GlobalKeywordCategoryController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalKeywordCategoryController.php',
                'resource' => 'app/Http/Resources/KeywordCategoryResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('global-keywords', [apiV2GlobalKeywordController::class, 'store'])->middleware('can:manage-users');",
                'controller' => 'app/Http/Controllers/Api/v2/GlobalKeywordController.php',
                'resource' => 'app/Http/Resources/KeywordResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('keyword-categories', [apiV2KeywordCategoryController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/KeywordCategoryController.php',
                'resource' => 'app/Http/Resources/KeywordCategoryResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
                'minIdNeedleCount' => 1,
            ],
            [
                'route' => "Route::post('keywords', [apiV2KeywordController::class, 'store']);",
                'controller' => 'app/Http/Controllers/Api/v2/KeywordController.php',
                'resource' => 'app/Http/Resources/KeywordResource.php',
                'statusNeedle' => '->setStatusCode(Response::HTTP_CREATED);',
                'idNeedle' => "'id' =>",
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

    public function test_v2_update_endpoints_document_partial_update_and_preserve_omitted_relations_in_code(): void
    {
        $letterController = file_get_contents(base_path('app/Http/Controllers/Api/v2/LetterController.php'));
        $identityController = file_get_contents(base_path('app/Http/Controllers/Api/v2/IdentityController.php'));
        $globalIdentityController = file_get_contents(base_path('app/Http/Controllers/Api/v2/GlobalIdentityController.php'));
        $letterRequest = file_get_contents(base_path('app/Http/Requests/Api/v2/LetterRequest.php'));
        $conventions = file_get_contents(base_path('docs/V2_API_CONVENTIONS.md'));

        $this->assertIsString($letterController);
        $this->assertIsString($identityController);
        $this->assertIsString($globalIdentityController);
        $this->assertIsString($letterRequest);
        $this->assertIsString($conventions);

        $this->assertStringContainsString('Partial update semantics. Omitted fields remain unchanged', $letterController);
        $this->assertStringContainsString('Partial update semantics. Omitted fields remain unchanged', $identityController);
        $this->assertStringContainsString('Partial update semantics. Omitted fields remain unchanged', $globalIdentityController);
        $this->assertStringContainsString('property: "client_meta"', $letterRequest);

        $this->assertStringContainsString("if (\$request->exists('recipients')) {", $letterController);
        $this->assertStringContainsString("if (\$request->exists('copies')) {", $letterController);
        $this->assertStringContainsString("\$hasProfessionInput = array_key_exists('professions', \$validated)", $identityController);
        $this->assertStringContainsString("if (array_key_exists('professions', \$validated)) {", $globalIdentityController);

        $this->assertStringContainsString('- omitted field on `PUT`: leave the current value unchanged', $conventions);
        $this->assertStringContainsString('- `client_meta` is the supported bucket for client-owned payload data.', $conventions);
    }

    public function test_simpler_v2_update_endpoints_document_partial_update_and_client_meta_in_openapi(): void
    {
        $controllers = [
            'app/Http/Controllers/Api/v2/LocationController.php',
            'app/Http/Controllers/Api/v2/GlobalLocationController.php',
            'app/Http/Controllers/Api/v2/PlaceController.php',
            'app/Http/Controllers/Api/v2/GlobalPlaceController.php',
            'app/Http/Controllers/Api/v2/KeywordController.php',
            'app/Http/Controllers/Api/v2/ProfessionController.php',
            'app/Http/Controllers/Api/v2/KeywordCategoryController.php',
            'app/Http/Controllers/Api/v2/ProfessionCategoryController.php',
            'app/Http/Controllers/Api/v2/GlobalKeywordController.php',
            'app/Http/Controllers/Api/v2/GlobalProfessionController.php',
            'app/Http/Controllers/Api/v2/GlobalKeywordCategoryController.php',
            'app/Http/Controllers/Api/v2/GlobalProfessionCategoryController.php',
        ];

        foreach ($controllers as $controller) {
            $code = file_get_contents(base_path($controller));

            $this->assertIsString($code);
            $this->assertStringContainsString('Partial update semantics. Omitted fields remain unchanged', $code, $controller);
            $this->assertStringContainsString('property: "client_meta"', $code, $controller);
        }
    }

    public function test_v2_create_endpoints_document_client_meta_in_openapi_examples(): void
    {
        $controllers = [
            'app/Http/Controllers/Api/v2/LetterController.php',
            'app/Http/Controllers/Api/v2/IdentityController.php',
            'app/Http/Controllers/Api/v2/GlobalIdentityController.php',
            'app/Http/Controllers/Api/v2/LocationController.php',
            'app/Http/Controllers/Api/v2/GlobalLocationController.php',
            'app/Http/Controllers/Api/v2/PlaceController.php',
            'app/Http/Controllers/Api/v2/GlobalPlaceController.php',
            'app/Http/Controllers/Api/v2/KeywordController.php',
            'app/Http/Controllers/Api/v2/GlobalKeywordController.php',
            'app/Http/Controllers/Api/v2/ProfessionController.php',
            'app/Http/Controllers/Api/v2/GlobalProfessionController.php',
            'app/Http/Controllers/Api/v2/KeywordCategoryController.php',
            'app/Http/Controllers/Api/v2/GlobalKeywordCategoryController.php',
            'app/Http/Controllers/Api/v2/ProfessionCategoryController.php',
            'app/Http/Controllers/Api/v2/GlobalProfessionCategoryController.php',
        ];

        foreach (array_slice($controllers, 1) as $controller) {
            $code = file_get_contents(base_path($controller));

            $this->assertIsString($code);
            $this->assertStringContainsString('property: "client_meta"', $code, $controller);
        }

        $letterController = file_get_contents(base_path('app/Http/Controllers/Api/v2/LetterController.php'));
        $letterRequest = file_get_contents(base_path('app/Http/Requests/Api/v2/LetterRequest.php'));

        $this->assertIsString($letterController);
        $this->assertIsString($letterRequest);
        $this->assertStringContainsString('ref: "#/components/schemas/LetterUpsertRequest"', $letterController);
        $this->assertStringContainsString('property: "client_meta"', $letterRequest);
    }

    public function test_public_examples_prefer_current_external_contract_field_names(): void
    {
        $liveCurl = file_get_contents(base_path('tests/Feature/Api/V2/LiveCreateEndpointsCurlTest.php'));
        $identityController = file_get_contents(base_path('app/Http/Controllers/Api/v2/IdentityController.php'));
        $globalIdentityController = file_get_contents(base_path('app/Http/Controllers/Api/v2/GlobalIdentityController.php'));

        $this->assertIsString($liveCurl);
        $this->assertIsString($identityController);
        $this->assertIsString($globalIdentityController);

        $this->assertStringNotContainsString("'category' =>", $liveCurl);
        $this->assertStringNotContainsString("'profession_category_id' =>", $liveCurl);
        $this->assertStringNotContainsString("'keyword_category_id' =>", $liveCurl);
        $this->assertStringContainsString("'category_id' =>", $liveCurl);

        $this->assertStringNotContainsString('"global_identity_id" => 1', $identityController);
        $this->assertStringContainsString('"global_identity" => [', $identityController);
        $this->assertStringContainsString('"reference" => "global-1"', $identityController);

        $this->assertStringNotContainsString('"global_identity_id" => 1', $globalIdentityController);
        $this->assertStringContainsString('"reference" => "local-1335"', $globalIdentityController);
    }
}
