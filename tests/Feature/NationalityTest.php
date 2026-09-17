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
