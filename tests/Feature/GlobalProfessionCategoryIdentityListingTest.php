<?php

namespace Tests\Feature;

use App\Services\GlobalProfessionCategoryIdentityListing;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GlobalProfessionCategoryIdentityListingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('table_prefix');
        });
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('domain');
        });
        foreach (['sample-alpha', 'sample-beta', 'sample-gamma'] as $index => $prefix) {
            DB::table('tenants')->insert(['id' => $index + 1, 'name' => 'Sample ' . $index, 'table_prefix' => $prefix]);
            Schema::create($prefix . '__identities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });
            Schema::create($prefix . '__identity_profession', function (Blueprint $table) {
                $table->unsignedBigInteger('identity_id');
                $table->unsignedBigInteger('global_profession_id')->nullable();
                $table->unsignedBigInteger('profession_id')->nullable();
            });
            DB::table($prefix . '__identities')->insert(['id' => 7, 'name' => 'Shared display name']);
            DB::table($prefix . '__identity_profession')->insert(['identity_id' => 7, 'global_profession_id' => 51]);
        }
        DB::table('domains')->insert([
            ['id' => 1, 'tenant_id' => 1, 'domain' => 'alpha.example.test'],
            ['id' => 2, 'tenant_id' => 1, 'domain' => 'alpha-alias.example.test'],
            ['id' => 3, 'tenant_id' => 2, 'domain' => 'beta.example.test'],
        ]);
        DB::table('sample-alpha__identity_profession')->insert([
            ['identity_id' => 7, 'global_profession_id' => 52],
            ['identity_id' => 7, 'global_profession_id' => 51],
        ]);
        DB::table('sample-alpha__identities')->insert([
            ['id' => 8, 'name' => 'Shared display name'],
            ['id' => 9, 'name' => 'Unrelated identity'],
        ]);
        DB::table('sample-alpha__identity_profession')->insert([
            ['identity_id' => 8, 'global_profession_id' => 51, 'profession_id' => null],
            ['identity_id' => 9, 'global_profession_id' => 99, 'profession_id' => null],
            ['identity_id' => 9, 'global_profession_id' => null, 'profession_id' => 51],
        ]);
        Schema::create('global_identities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('global_identity_profession', function (Blueprint $table) {
            $table->unsignedBigInteger('global_identity_id');
            $table->unsignedBigInteger('global_profession_id');
        });
        DB::table('global_identities')->insert(['id' => 10, 'name' => 'Global identity']);
        DB::table('global_identity_profession')->insert(['global_identity_id' => 10, 'global_profession_id' => 51]);
    }

    public function test_unique_identities_are_grouped_by_tenant_without_losing_same_name_or_same_id_records(): void
    {
        $groups = app(GlobalProfessionCategoryIdentityListing::class)->forProfessions([51, 52]);
        $this->assertSame([1, 2, 3], $groups->pluck('tenant_id')->all());
        $this->assertSame([7, 8], $groups[0]->identities->pluck('id')->all());
        $this->assertSame([7], $groups[1]->identities->pluck('id')->all());
        $this->assertSame([7], $groups[2]->identities->pluck('id')->all());
        $this->assertSame(4, $groups->sum(fn($group) => $group->identities->count()));
        $this->assertSame('alpha.example.test', $groups[0]->tenant_domain);
        $this->assertNull($groups[2]->tenant_domain);
    }

    public function test_rendered_groups_show_tenant_labels_unique_counts_and_owning_tenant_links(): void
    {
        app()->setLocale('en');
        $identityGroups = app(GlobalProfessionCategoryIdentityListing::class)->forProfessions([51, 52]);
        $html = view('pages.global-professions-categories.identity-list', compact('identityGroups'))->render();
        $this->assertStringContainsString('Persons and institutions: 4', $html);
        $this->assertStringContainsString('Sample 0: 2', $html);
        $this->assertStringContainsString('Sample 1: 1', $html);
        $this->assertStringContainsString('sample-gamma', $html);
        $this->assertSame(1, substr_count($html, 'https://alpha.example.test/identities/7/edit'));
        $this->assertStringContainsString('https://beta.example.test/identities/7/edit', $html);
        $this->assertStringNotContainsString('alpha-alias', $html);
        $this->assertStringNotContainsString('Global identity', $html);
        $this->assertStringNotContainsString('Unrelated identity', $html);
        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_current_tenant_is_first_and_is_the_only_expanded_group(): void
    {
        tenancy()->tenant = new \App\Models\Tenant(['table_prefix' => 'sample-beta']);
        tenancy()->initialized = true;

        try {
            $identityGroups = app(GlobalProfessionCategoryIdentityListing::class)->forProfessions([51, 52]);
            $this->assertSame([2, 1, 3], $identityGroups->pluck('tenant_id')->all());
            $html = view('pages.global-professions-categories.identity-list', compact('identityGroups'))->render();
            $document = new \DOMDocument();
            @$document->loadHTML($html);
            $details = $document->getElementsByTagName('details');
            $this->assertSame(3, $details->length);
            $this->assertTrue($details->item(0)->hasAttribute('open'));
            $this->assertFalse($details->item(1)->hasAttribute('open'));
            $this->assertFalse($details->item(2)->hasAttribute('open'));
            $this->assertStringContainsString('Sample 1', $details->item(0)->textContent);
        } finally {
            tenancy()->initialized = false;
            tenancy()->tenant = null;
        }
    }

    public function test_empty_or_unused_professions_show_no_groups(): void
    {
        $service = app(GlobalProfessionCategoryIdentityListing::class);
        $this->assertCount(0, $service->forProfessions([]));
        $this->assertCount(0, $service->forProfessions([100]));
        $html = view('pages.global-professions-categories.identity-list', ['identityGroups' => collect()])->render();
        $this->assertStringContainsString(__('hiko.no_attached_persons'), $html);
    }
}
