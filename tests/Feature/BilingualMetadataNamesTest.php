<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class BilingualMetadataNamesTest extends TestCase
{
    private string $databasePath;

    private const ENTITIES = [
        'Profession' => ['professions', 'profession', 'profession_category_id'],
        'ProfessionCategory' => ['profession-categories', 'profession-category', null],
        'Keyword' => ['keywords', 'keyword', 'keyword_category_id'],
        'KeywordCategory' => ['keyword-categories', 'keyword-category', null],
        'GlobalProfession' => ['global-professions', 'global-profession', 'profession_category_id'],
        'GlobalProfessionCategory' => ['global-profession-categories', 'global-profession-category', null],
        'GlobalKeyword' => ['global-keywords', 'global-keyword', 'keyword_category_id'],
        'GlobalKeywordCategory' => ['global-keyword-categories', 'global-keyword-category', null],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite is required.');
        }
        $this->databasePath = tempnam(sys_get_temp_dir(), 'bilingual-metadata-');
        $connection = array_merge(config('database.connections.sqlite'), ['database' => $this->databasePath, 'prefix' => '']);
        foreach (['sqlite', 'tenant'] as $name) {
            Config::set("database.connections.{$name}", $connection);
            DB::purge($name);
        }
        Config::set('scout.driver', null);
        tenancy()->initialize(new Tenant(['id' => 1, 'table_prefix' => 'test-tenant']));
        Config::set('database.connections.tenant.prefix', '');
        DB::purge('tenant');
        foreach (self::ENTITIES as $entity => [, , $category]) {
            $model = $this->model($entity);
            Schema::connection($model->getConnectionName())->create($model->getTable(), function (Blueprint $table) use ($category) {
                $table->id();
                $table->json('name');
                if ($category) {
                    $table->unsignedBigInteger($category)->nullable();
                }
                $table->timestamps();
            });
            $model->getConnection()->table($model->getTable())->insert([
                'id' => 1, 'name' => json_encode(['cs' => 'Ukázka', 'en' => 'Example']),
                ...($category ? [$category => 1] : []),
            ]);
        }
    }

    protected function tearDown(): void
    {
        if (isset($this->databasePath)) {
            tenancy()->end();
            DB::disconnect('tenant');
            DB::disconnect('sqlite');
            unlink($this->databasePath);
        }
        parent::tearDown();
    }

    public function test_create_and_web_update_require_both_names_for_every_entity(): void
    {
        foreach (self::ENTITIES as $entity => [, , $category]) {
            foreach (['web-create', 'web-update', 'api-create'] as $operation) {
                $valid = ['cs' => ' Ukázka ', 'en' => ' Example ', ...($category ? [$category => 1] : [])];
                [$request, $validator] = $this->validateNames($entity, $operation, $valid);
                $this->assertTrue($validator->passes(), "{$entity} {$operation}: ".json_encode($validator->errors()));
                $this->assertSame('Example', $request->validated()['en']);
                foreach (['cs', 'en'] as $locale) {
                    foreach ([null, '', '   ', str_repeat('x', 256), [], 42] as $bad) {
                        [, $validator] = $this->validateNames($entity, $operation, array_replace($valid, [$locale => $bad]));
                        $this->assertArrayHasKey($locale, $validator->errors()->toArray(), "{$entity} {$operation}");
                    }
                    $missing = $valid;
                    unset($missing[$locale]);
                    [, $validator] = $this->validateNames($entity, $operation, $missing);
                    $this->assertArrayHasKey($locale, $validator->errors()->toArray());
                }
            }
        }
    }

    public function test_api_creation_persists_both_names_for_every_entity(): void
    {
        foreach (self::ENTITIES as $entity => [, , $category]) {
            [$request, $validator] = $this->validateNames($entity, 'api-create', [
                'cs' => 'Nová ukázka', 'en' => str_repeat('x', 255), ...($category ? [$category => 1] : []),
            ]);
            $this->assertTrue($validator->passes(), $entity);
            $controller = "App\\Http\\Controllers\\Api\\v2\\{$entity}Controller";
            $response = (new $controller())->store($request);
            $this->assertSame(201, $response->getStatusCode(), $entity);
            $created = $this->model($entity)->findOrFail(2)->getTranslations('name');
            $this->assertSame('Nová ukázka', $created['cs']);
            $this->assertSame(str_repeat('x', 255), $created['en']);
        }
    }

    public function test_api_partial_updates_validate_and_persist_the_resulting_record(): void
    {
        foreach (self::ENTITIES as $entity => [, , $category]) {
            $controllerClass = "App\\Http\\Controllers\\Api\\v2\\{$entity}Controller";
            $unrelated = $category ? ['category_id' => 1] : ['client_meta' => ['example' => 'value']];
            [$request, $validator] = $this->validateNames($entity, 'api-update', $unrelated);
            $this->assertTrue($validator->passes(), $entity);
            (new $controllerClass())->update($request, 1);
            $this->assertSame('Example', $this->model($entity)->findOrFail(1)->getTranslation('name', 'en', false));

            foreach (['cs', 'en'] as $locale) {
                foreach ([null, '', '   '] as $bad) {
                    [, $validator] = $this->validateNames($entity, 'api-update', [$locale => $bad]);
                    $this->assertArrayHasKey($locale, $validator->errors()->toArray());
                }
            }
            foreach ([null, '', '   '] as $missing) {
                $this->setName($entity, ['cs' => 'Ukázka', 'en' => $missing]);
                [, $validator] = $this->validateNames($entity, 'api-update', $unrelated);
                $this->assertArrayHasKey('en', $validator->errors()->toArray(), $entity);
            }
            $this->setName($entity, ['cs' => 'Ukázka']);
            [$request, $validator] = $this->validateNames($entity, 'api-update', ['en' => 'Repaired example']);
            $this->assertTrue($validator->passes(), $entity);
            (new $controllerClass())->update($request, 1);
            $name = $this->model($entity)->findOrFail(1)->getTranslations('name');
            $this->assertSame(['cs' => 'Ukázka', 'en' => 'Repaired example'], $name);

            $this->setName($entity, []);
            [, $validator] = $this->validateNames($entity, 'api-update', ['en' => 'Example']);
            $this->assertArrayHasKey('cs', $validator->errors()->toArray());
        }
    }

    public function test_global_name_alias_cannot_bypass_validation_and_supports_partial_repairs(): void
    {
        foreach (self::ENTITIES as $entity => [, , $category]) {
            if (!str_starts_with($entity, 'Global')) {
                continue;
            }
            foreach (['Plain name', ['cs' => 'Ukázka'], ['cs' => '', 'en' => 'Example'], ['cs' => 42, 'en' => 'Example']] as $bad) {
                [, $validator] = $this->validateNames($entity, 'api-create', ['name' => $bad, ...($category ? [$category => 1] : [])]);
                $this->assertFalse($validator->passes(), $entity);
            }
            foreach ([['cs' => 'Ukázka', 'en' => 'Example'], '{"cs":"Ukázka","en":"Example"}'] as $valid) {
                [, $validator] = $this->validateNames($entity, 'api-create', ['name' => $valid, ...($category ? [$category => 1] : [])]);
                $this->assertTrue($validator->passes(), $entity);
            }
            [, $validator] = $this->validateNames($entity, 'api-update', ['name' => ['en' => null]]);
            $this->assertArrayHasKey('en', $validator->errors()->toArray());
            $this->setName($entity, ['cs' => 'Ukázka']);
            [$request, $validator] = $this->validateNames($entity, 'api-update', ['name' => ['en' => 'Repair']]);
            $this->assertTrue($validator->passes(), $entity);
            $controller = "App\\Http\\Controllers\\Api\\v2\\{$entity}Controller";
            (new $controller())->update($request, 1);
            $this->assertSame('Repair', $this->model($entity)->findOrFail(1)->getTranslation('name', 'en', false));
        }
    }

    public function test_consistency_scans_flag_actual_missing_translations_in_both_scopes(): void
    {
        foreach (['Professions' => ['Profession', 'GlobalProfession'], 'Keywords' => ['Keyword', 'GlobalKeyword']] as $component => $entities) {
            $class = "App\\Livewire\\{$component}ConsistencyCheck";
            foreach (['cs', 'en'] as $missingLocale) {
                foreach ([null, '', '   '] as $blank) {
                    foreach ($entities as $entity) {
                        $this->setName($entity, array_replace(['cs' => 'Ukázka', 'en' => 'Example'], [$missingLocale => $blank]));
                    }
                    $scan = new $class();
                    $scan->scan();
                    $this->assertCount(2, $scan->issues);
                    $this->assertSame(['local', 'global'], array_column($scan->issues, 'type'));
                }
            }
        }
    }

    private function model(string $entity)
    {
        $class = "App\\Models\\{$entity}";
        return new $class();
    }

    private function setName(string $entity, array $name): void
    {
        $model = $this->model($entity);
        $model->getConnection()->table($model->getTable())->where('id', 1)->update(['name' => json_encode($name)]);
    }

    private function validateNames(string $entity, string $operation, array $payload): array
    {
        [$plural, $singular] = self::ENTITIES[$entity];
        $update = str_ends_with($operation, 'update');
        $path = (str_starts_with($operation, 'api') ? '/api/v2/' : '/').($update ? "{$singular}/1" : $plural);
        $class = "App\\Http\\Requests\\{$entity}Request";
        $request = $class::create($path, $update ? 'PUT' : 'POST', $payload);
        $request->setRouteResolver(fn () => new class {
            public function parameter($key, $default = null) { return $key === 'id' ? 1 : $default; }
        });
        $request->prepareForValidation();
        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);
        $request->setValidator($validator);
        return [$request, $validator];
    }
}
