<?php

namespace Tests\Feature;

use App\Http\Controllers\GlobalProfessionCategoryController;
use App\Http\Controllers\ProfessionCategoryController;
use App\Http\Controllers\Api\v2\GlobalProfessionCategoryController as ApiGlobalProfessionCategoryController;
use App\Http\Controllers\Api\v2\ProfessionCategoryController as ApiProfessionCategoryController;
use App\Http\Requests\GlobalProfessionRequest;
use App\Http\Requests\ProfessionRequest;
use App\Livewire\ProfessionsConsistencyCheck;
use App\Models\GlobalProfessionCategory;
use App\Models\ProfessionCategory;
use App\Models\Tenant;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProfessionCategoryRequirementTest extends TestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_sqlite extension is required for this integration test.');
        }

        $this->databasePath = tempnam(sys_get_temp_dir(), 'hiko-profession-category-');
        $connection = array_merge(config('database.connections.sqlite'), [
            'database' => $this->databasePath,
            'prefix' => '',
        ]);

        Config::set('database.connections.sqlite', $connection);
        Config::set('database.connections.tenant', $connection);
        DB::purge('sqlite');
        DB::purge('tenant');

        tenancy()->initialize(new Tenant([
            'id' => 1,
            'table_prefix' => 'test-tenant',
        ]));

        Config::set('database.connections.tenant.prefix', '');
        DB::purge('tenant');

        $this->createSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        if (isset($this->databasePath)) {
            tenancy()->end();
            DB::disconnect('sqlite');
            DB::disconnect('tenant');
            @unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function test_web_requests_require_a_category_for_local_and_global_professions(): void
    {
        $local = TestableProfessionRequest::create('/professions', 'POST', [
            'cs' => 'Synthetic local profession',
            'en' => 'Synthetic local profession',
            'profession_category_id' => null,
        ]);
        $global = TestableGlobalProfessionRequest::create('/global-professions', 'POST', [
            'cs' => 'Synthetic global profession',
            'en' => 'Synthetic global profession',
            'profession_category_id' => null,
        ]);

        $this->assertArrayHasKey('profession_category_id', $local->validatorForTest()->errors()->toArray());
        $this->assertArrayHasKey('profession_category_id', $global->validatorForTest()->errors()->toArray());
    }

    public function test_v2_create_requires_a_valid_category_alias_for_both_scopes(): void
    {
        $localMissing = TestableProfessionRequest::create('/api/v2/professions', 'POST', [
            'cs' => 'Synthetic local profession',
            'en' => 'Synthetic local profession',
        ]);
        $globalMissing = TestableGlobalProfessionRequest::create('/api/v2/global-professions', 'POST', [
            'cs' => 'Synthetic global profession',
            'en' => 'Synthetic global profession',
        ]);

        $this->assertArrayHasKey('profession_category_id', $localMissing->validatorForTest()->errors()->toArray());
        $this->assertArrayHasKey('profession_category_id', $globalMissing->validatorForTest()->errors()->toArray());

        $localValid = TestableProfessionRequest::create('/api/v2/professions', 'POST', [
            'cs' => 'Synthetic local profession',
            'en' => 'Synthetic local profession',
            'category_id' => 10,
        ]);
        $globalValid = TestableGlobalProfessionRequest::create('/api/v2/global-professions', 'POST', [
            'cs' => 'Synthetic global profession',
            'en' => 'Synthetic global profession',
            'category_id' => 20,
        ]);

        $this->assertTrue($localValid->validatorForTest()->passes());
        $this->assertTrue($globalValid->validatorForTest()->passes());
    }

    public function test_v2_partial_update_preserves_valid_categories_but_cannot_keep_or_set_null(): void
    {
        $this->assertTrue($this->localUpdateValidator(1, [])->passes());
        $this->assertFalse($this->localUpdateValidator(1, ['category_id' => null])->passes());
        $this->assertFalse($this->localUpdateValidator(2, [])->passes());

        $this->assertTrue($this->globalUpdateValidator(101, [])->passes());
        $this->assertFalse($this->globalUpdateValidator(101, ['category_id' => null])->passes());
        $this->assertFalse($this->globalUpdateValidator(102, [])->passes());
    }

    public function test_categories_with_professions_cannot_be_deleted(): void
    {
        $localCategory = ProfessionCategory::query()->findOrFail(10);
        (new ProfessionCategoryController())->destroy($localCategory);

        $this->assertDatabaseHas('test-tenant__profession_categories', ['id' => 10]);
        $this->assertTrue(session('errors')->has('category'));

        session()->forget('errors');

        $globalCategory = GlobalProfessionCategory::query()->findOrFail(20);
        (new GlobalProfessionCategoryController())->destroy($globalCategory);

        $this->assertDatabaseHas('global_profession_categories', ['id' => 20]);
        $this->assertTrue(session('errors')->has('category'));
    }

    public function test_v2_category_deletion_returns_conflict_when_professions_are_attached(): void
    {
        $localResponse = (new ApiProfessionCategoryController())->destroy(10);
        $globalResponse = (new ApiGlobalProfessionCategoryController())->destroy(20);

        $this->assertSame(409, $localResponse->getStatusCode());
        $this->assertSame(409, $globalResponse->getStatusCode());
        $this->assertDatabaseHas('test-tenant__profession_categories', ['id' => 10]);
        $this->assertDatabaseHas('global_profession_categories', ['id' => 20]);
    }

    public function test_consistency_check_reports_uncategorized_professions_in_both_scopes(): void
    {
        $component = new ProfessionsConsistencyCheck();
        $component->scope = 'all';
        $component->scan();

        $issues = collect($component->issues);

        $this->assertTrue($issues->contains(fn (array $issue) => $issue['type'] === 'local' && $issue['id'] === 2));
        $this->assertTrue($issues->contains(fn (array $issue) => $issue['type'] === 'global' && $issue['id'] === 102));
    }

    public function test_forms_submit_the_validated_field_and_require_a_selection(): void
    {
        foreach ([
            resource_path('views/pages/professions/form.blade.php'),
            resource_path('views/pages/global-professions/form.blade.php'),
        ] as $form) {
            $contents = file_get_contents($form);

            $this->assertIsString($contents);
            $this->assertStringContainsString('name="profession_category_id"', $contents);
            $this->assertMatchesRegularExpression('/<x-select[^>]+name="profession_category_id"[^>]+required>/', $contents);
            $this->assertStringContainsString("@error('profession_category_id')", $contents);
        }
    }

    private function localUpdateValidator(int $id, array $payload): ValidatorContract
    {
        $request = TestableProfessionRequest::create("/api/v2/profession/{$id}", 'PUT', $payload);
        $request->setRouteResolver(fn () => new TestRouteParameters(['id' => $id]));

        return $request->validatorForTest();
    }

    private function globalUpdateValidator(int $id, array $payload): ValidatorContract
    {
        $request = TestableGlobalProfessionRequest::create("/api/v2/global-profession/{$id}", 'PUT', $payload);
        $request->setRouteResolver(fn () => new TestRouteParameters(['id' => $id]));

        return $request->validatorForTest();
    }

    private function createSchema(): void
    {
        Schema::connection('tenant')->create('test-tenant__profession_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->timestamps();
        });
        Schema::connection('tenant')->create('test-tenant__professions', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->unsignedBigInteger('profession_category_id')->nullable();
            $table->timestamps();
        });
        Schema::create('global_profession_categories', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->timestamps();
        });
        Schema::create('global_professions', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->unsignedBigInteger('profession_category_id')->nullable();
            $table->timestamps();
        });
    }

    private function seedFixtures(): void
    {
        DB::connection('tenant')->table('test-tenant__profession_categories')->insert([
            'id' => 10,
            'name' => '{"cs":"Místní kategorie","en":"Local category"}',
        ]);
        DB::connection('tenant')->table('test-tenant__professions')->insert([
            [
                'id' => 1,
                'name' => '{"cs":"Zařazená profese","en":"Categorized profession"}',
                'profession_category_id' => 10,
            ],
            [
                'id' => 2,
                'name' => '{"cs":"Nezařazená profese","en":"Uncategorized profession"}',
                'profession_category_id' => null,
            ],
        ]);
        DB::table('global_profession_categories')->insert([
            'id' => 20,
            'name' => '{"cs":"Globální kategorie","en":"Global category"}',
        ]);
        DB::table('global_professions')->insert([
            [
                'id' => 101,
                'name' => '{"cs":"Zařazená globální profese","en":"Categorized global profession"}',
                'profession_category_id' => 20,
            ],
            [
                'id' => 102,
                'name' => '{"cs":"Nezařazená globální profese","en":"Uncategorized global profession"}',
                'profession_category_id' => null,
            ],
        ]);
    }
}

class TestableProfessionRequest extends ProfessionRequest
{
    public function validatorForTest(): ValidatorContract
    {
        $this->prepareForValidation();
        $validator = Validator::make($this->all(), $this->rules());
        $this->withValidator($validator);

        return $validator;
    }
}

class TestableGlobalProfessionRequest extends GlobalProfessionRequest
{
    public function validatorForTest(): ValidatorContract
    {
        $this->prepareForValidation();
        $validator = Validator::make($this->all(), $this->rules());
        $this->withValidator($validator);

        return $validator;
    }
}

class TestRouteParameters
{
    public function __construct(private array $parameters)
    {
    }

    public function parameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }
}
