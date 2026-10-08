<?php

namespace Tests\Feature;

use App\Models\GlobalIdentity;
use App\Models\Identity;
use App\Models\Nationality;
use App\Http\Resources\IdentityResource;
use App\Http\Requests\IdentityRequest;
use App\Http\Requests\GlobalIdentityRequest;
use App\Http\Requests\NationalityRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Schema\Blueprint;
use Tests\Support\NationalityFixtures;
use Tests\TestCase;

class NationalityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        foreach (['global_identities', 'team-a__identities', 'team-b__identities'] as $source) {
            Schema::create($source, function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->default('person');
                $table->unsignedBigInteger('global_identity_id')->nullable();
                $table->timestamps();
            });
        }
        NationalityFixtures::create(['global_identities', 'team-a__identities', 'team-b__identities']);
        (require database_path('migrations/2026_10_08_000000_create_nationality_search_expansions.php'))->up();
    }

    public function test_expansion_is_global_directed_optional_and_one_step(): void
    {
        foreach ([1 => ['Starý termín', 'Oldterm'], 2 => ['Druhý termín', 'Secondterm'], 3 => ['Třetí termín', 'Thirdterm'], 4 => ['Čtvrtý termín', 'Fourthterm']] as $id => [$cs, $en]) {
            Nationality::findOrFail($id)->update(['name' => compact('cs', 'en')]);
        }
        DB::table('nationality_search_expansions')->insert([
            ['source_nationality_id' => 1, 'target_nationality_id' => 2],
            ['source_nationality_id' => 1, 'target_nationality_id' => 3],
            ['source_nationality_id' => 3, 'target_nationality_id' => 1],
            ['source_nationality_id' => 2, 'target_nationality_id' => 4],
        ]);
        foreach (['global_identities', 'team-a__identities', 'team-b__identities'] as $source) {
            foreach ([1, 2, 3, 4] as $id) {
                $model = $source === 'global_identities' ? new GlobalIdentity() : $this->local();
                $model->setTable($source);
                $model->fill(['name' => 'Example ' . $id, 'nationalities' => $id === 2 ? [2, 3] : [$id]])->save();
            }
            $find = function ($term, $mode = 'direct') use ($source) {
                $query = DB::table($source);
                \App\Support\NationalityFilter::applyFilters($query, ['nationality' => $term, 'nationality_match' => $mode]);
                return $query->orderBy('id')->pluck('name')->all();
            };
            $this->assertSame(['Example 1'], $find('OLD'));
            $this->assertSame(['Example 1', 'Example 2', 'Example 3'], $find('old', 'expanded'));
            $this->assertSame(['Example 1', 'Example 2', 'Example 3'], $find('Starý', 'expanded'));
            $this->assertSame(['Example 1', 'Example 2', 'Example 3'], $find('third', 'expanded'));
            $this->assertSame(['Example 2', 'Example 4'], $find('second', 'expanded'));
            $this->assertSame([], $find('unmatched', 'expanded'));
        }
        // Expansion source need not be assigned in the searched tenant.
        DB::table('team-b__identity_nationality')->where('nationality_id', 1)->delete();
        $query = DB::table('team-b__identities');
        \App\Support\NationalityFilter::apply($query, 'old', 'expanded');
        $this->assertSame(['Example 2', 'Example 3'], $query->orderBy('id')->pluck('name')->all());
    }

    public function test_expansion_validation_and_permissions(): void
    {
        $request = new \App\Http\Requests\NationalityExpansionRequest();
        $request->merge(['source_nationality_id' => 1]);
        foreach ([['source_nationality_id' => 1, 'target_nationality_id' => 1], ['source_nationality_id' => 1, 'target_nationality_id' => 999], []] as $data) {
            $this->assertTrue(Validator::make($data, $request->rules())->fails());
        }
        $pair = ['source_nationality_id' => 1, 'target_nationality_id' => 2];
        $this->assertFalse(Validator::make($pair, $request->rules())->fails());
        DB::table('nationality_search_expansions')->insert($pair);
        $this->assertTrue(Validator::make($pair, $request->rules())->fails());
        $this->assertFalse($request->authorize());
        $request->setUserResolver(fn() => new class {
            public function can($ability) { return $ability === 'manage-users'; }
        });
        $this->assertTrue($request->authorize());
    }

    public function test_invalid_api_matching_mode_is_rejected(): void
    {
        $rules = (new \App\Http\Requests\Api\v2\IdentityIndexRequest())->rules();
        foreach (['invalid', ['nationality' => ['invalid']], ['nationality_match' => 'recursive'], ['nationality_match' => null], ['nationality' => str_repeat('a', 256)]] as $filter) {
            $this->assertTrue(Validator::make(['filter' => $filter], $rules)->fails());
        }
        foreach ([[], ['nationality' => null], ['nationality' => ''], ['nationality' => 'Example', 'nationality_match' => 'expanded']] as $filter) {
            $this->assertFalse(Validator::make(['filter' => $filter], $rules)->fails());
        }
        $validator = Validator::make(['filter' => ['nationality' => 'Example', 'nationality_match' => 'expanded', 'unknown' => 'ignored']], $rules);
        $request = new \App\Http\Requests\Api\v2\IdentityIndexRequest();
        $request->setValidator($validator);
        $this->assertSame(['nationality' => 'Example', 'nationality_match' => 'expanded'], $request->filters());
    }

    public function test_catalogue_uses_locale_aware_alphabetical_order(): void
    {
        Nationality::query()->delete();
        foreach ([
            ['cs' => 'xhosská', 'en' => 'Xhosa'],
            ['cs' => 'íránská', 'en' => 'Iranian'],
            ['cs' => 'černohorská', 'en' => 'Montenegrin'],
            ['cs' => 'chorvatská', 'en' => 'Croatian'],
            ['cs' => 'haitská', 'en' => 'Haitian'],
            ['cs' => 'česká', 'en' => 'Czech'],
        ] as $name) {
            Nationality::create(['name' => $name]);
        }

        $originalLocale = app()->getLocale();
        try {
            app()->setLocale('cs');
            $this->assertSame(['černohorská', 'česká', 'haitská', 'chorvatská', 'íránská', 'xhosská'], Nationality::orderedForLocale()->pluck('name')->all());
            app()->setLocale('en');
            $this->assertSame(['Croatian', 'Czech', 'Haitian', 'Iranian', 'Montenegrin', 'Xhosa'], Nationality::orderedForLocale()->pluck('name')->all());
        } finally {
            app()->setLocale($originalLocale);
        }
    }

    private function local(string $prefix = 'team-a'): Identity
    {
        $model = new Identity();
        $model->setTable($prefix . '__identities');
        return $model;
    }

    public function test_ordered_assignments_survive_updates_and_remain_independent_across_scopes(): void
    {
        $global = GlobalIdentity::create(['name' => 'Example', 'nationalities' => [4, 1]]);
        $local = $this->local();
        $local->fill(['name' => 'Example', 'nationalities' => [3, 1], 'global_identity_id' => $global->id])->save();
        $other = $this->local('team-b');
        $other->fill(['name' => 'Example', 'nationalities' => [5]])->save();
        $this->assertSame([4, 1], $global->nationalities->pluck('id')->all());
        $this->assertSame([3, 1], $local->nationalities->pluck('id')->all());
        $this->assertSame([5], $other->nationalities->pluck('id')->all());
        $local->update(['name' => 'Changed']);
        $this->assertSame([3, 1], $local->fresh()->nationalities->pluck('id')->all());
        $local->update(['nationalities' => [1, 3]]);
        $this->assertSame([1, 3], $local->nationalities->pluck('id')->all());
        $this->assertSame([0, 1], $local->nationalities->pluck('pivot.position')->all());
        $local->update(['nationalities' => []]);
        $this->assertCount(0, $local->nationalities);
        $this->assertSame([4, 1], $global->fresh()->nationalities->pluck('id')->all());
        $this->assertFalse(Schema::hasColumn('global_identities', 'nationality'));
    }

    public function test_resource_returns_ordered_bilingual_objects_without_old_text_field(): void
    {
        $global = GlobalIdentity::create(['name' => 'Example', 'nationalities' => [4, 1]]);
        $data = (new IdentityResource($global))->response()->getData(true)['data'];
        $this->assertArrayNotHasKey('nationality', $data);
        $this->assertSame([4, 1], array_column($data['nationalities'], 'id'));
        $this->assertSame(['cs' => 'německá', 'en' => 'German'], $data['nationalities'][0]['name']);
    }

    public function test_invalid_lists_are_rejected_and_omission_or_empty_list_is_valid(): void
    {
        tenancy()->tenant = new \App\Models\Tenant(['table_prefix' => 'team-a']);
        foreach ([new IdentityRequest(), new GlobalIdentityRequest()] as $request) {
            $rules = array_intersect_key($request->rules(), array_flip(['nationality', 'nationalities', 'nationalities.*']));
            foreach ([['nationalities' => null], ['nationalities' => '1'], ['nationalities' => [1, 1]], ['nationalities' => [999]], ['nationalities' => ['x' => 1]], ['nationality' => 'Czech']] as $bad) {
                $this->assertTrue(Validator::make($bad, $rules)->fails());
            }
            $this->assertFalse(Validator::make([], $rules)->fails());
            $this->assertFalse(Validator::make(['nationalities' => []], $rules)->fails());
            $this->assertFalse(Validator::make(['nationalities' => [4, 1]], $rules)->fails());
        }
    }

    protected function tearDown(): void
    {
        tenancy()->tenant = null;
        parent::tearDown();
    }

    public function test_catalogue_requires_both_nonblank_translations(): void
    {
        $rules = (new NationalityRequest())->rules();
        foreach ([['cs' => 'česká'], ['cs' => 'česká', 'en' => null], ['cs' => "\u{00a0}", 'en' => 'Czech']] as $bad) $this->assertTrue(Validator::make($bad, $rules)->fails());
        $this->assertFalse(Validator::make(['cs' => 'česká', 'en' => 'Czech'], $rules)->fails());
        $request = new NationalityRequest();
        $request->setUserResolver(fn() => new class {
            public function can($ability)
            {
                return false;
            }
        });
        $this->assertFalse($request->authorize());
        $request->setUserResolver(fn() => new class {
            public function can($ability)
            {
                return $ability === 'manage-users';
            }
        });
        $this->assertTrue($request->authorize());
    }

    public function test_failed_assignment_rolls_back_identity_save(): void
    {
        // Simulate a DB failure after the parent insert.
        DB::statement("CREATE TRIGGER reject_nationality BEFORE INSERT ON global_identity_nationality BEGIN SELECT RAISE(ABORT, 'synthetic failure'); END");
        try {
            GlobalIdentity::create(['name' => 'Must rollback', 'nationalities' => [1]]);
            $this->fail('Expected failure');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertDatabaseMissing('global_identities', ['name' => 'Must rollback']);
        }
    }
}
