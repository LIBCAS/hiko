<?php

namespace Tests\Feature;

use App\Http\Controllers\LetterController;
use App\Models\GlobalPlace;
use App\Models\Letter;
use App\Models\Location;
use App\Models\Manifestation;
use App\Models\OcrSnapshot;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LetterService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LetterDuplicationHistoryTest extends TestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->databasePath = tempnam(sys_get_temp_dir(), 'hiko-letter-history-');

        Config::set('database.connections.sqlite.database', $this->databasePath);
        Config::set('database.connections.tenant', array_merge(
            config('database.connections.sqlite'),
            ['database' => $this->databasePath]
        ));

        tenancy()->initialize(new Tenant([
            'id' => 1,
            'table_prefix' => 'test',
        ]));

        Bus::fake();

        Schema::connection('tenant')->create('test__users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('role')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamp('deactivated_at')->nullable();
        });

        Schema::connection('tenant')->create('test__letters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->timestamps();
            $table->integer('date_year')->nullable();
            $table->integer('date_month')->nullable();
            $table->integer('date_day')->nullable();
            $table->text('date_marked')->nullable();
            $table->boolean('date_uncertain')->default(false);
            $table->boolean('date_approximate')->default(false);
            $table->boolean('date_inferred')->default(false);
            $table->boolean('date_is_range')->default(false);
            $table->mediumText('date_note')->nullable();
            $table->date('date_computed')->nullable();
            $table->integer('range_year')->nullable();
            $table->integer('range_month')->nullable();
            $table->integer('range_day')->nullable();
            $table->boolean('author_inferred')->default(false);
            $table->boolean('author_uncertain')->default(false);
            $table->mediumText('author_note')->nullable();
            $table->boolean('recipient_inferred')->default(false);
            $table->boolean('recipient_uncertain')->default(false);
            $table->mediumText('recipient_note')->nullable();
            $table->boolean('destination_inferred')->default(false);
            $table->boolean('destination_uncertain')->default(false);
            $table->mediumText('destination_note')->nullable();
            $table->boolean('origin_inferred')->default(false);
            $table->boolean('origin_uncertain')->default(false);
            $table->mediumText('origin_note')->nullable();
            $table->mediumText('people_mentioned_note')->nullable();
            $table->longText('copies')->nullable();
            $table->longText('related_resources')->nullable();
            $table->longText('abstract')->nullable();
            $table->text('explicit')->nullable();
            $table->text('incipit')->nullable();
            $table->longText('content')->nullable();
            $table->longText('content_stripped')->nullable();
            $table->longText('history')->nullable();
            $table->text('copyright')->nullable();
            $table->text('languages')->nullable();
            $table->longText('notes_private')->nullable();
            $table->longText('notes_public')->nullable();
            $table->text('status')->nullable();
        });

        Schema::connection('tenant')->create('test__identities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('forename')->nullable();
            $table->string('type')->nullable();
        });

        Schema::connection('tenant')->create('test__identity_letter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id')->nullable();
            $table->unsignedBigInteger('global_identity_id')->nullable();
            $table->unsignedBigInteger('letter_id');
            $table->string('role', 100)->nullable();
            $table->integer('position')->nullable();
            $table->text('marked')->nullable();
            $table->text('salutation')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('test__letter_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('letter_id');
            $table->unsignedBigInteger('user_id');
            $table->unique(['letter_id', 'user_id']);
        });

        Schema::connection('tenant')->create('test__locations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('type');
        });

        Schema::connection('tenant')->create('test__places', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('division')->nullable();
        });

        Schema::connection('tenant')->create('test__keywords', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->json('name')->nullable();
        });

        Schema::connection('tenant')->create('test__test__keywords', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->json('name')->nullable();
        });

        Schema::connection('tenant')->create('test__letter_place', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('letter_id');
            $table->unsignedBigInteger('place_id')->nullable();
            $table->unsignedBigInteger('global_place_id')->nullable();
            $table->string('role', 100);
            $table->integer('position')->nullable();
            $table->text('marked')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('test__keyword_letter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('keyword_id')->nullable();
            $table->unsignedBigInteger('global_keyword_id')->nullable();
            $table->unsignedBigInteger('letter_id');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('test__test__keyword_letter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('keyword_id')->nullable();
            $table->unsignedBigInteger('global_keyword_id')->nullable();
            $table->unsignedBigInteger('letter_id');
            $table->timestamps();
        });

        Schema::connection('tenant')->create('test__manifestations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('letter_id');
            $table->unsignedBigInteger('repository_id')->nullable();
            $table->unsignedBigInteger('archive_id')->nullable();
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->unsignedBigInteger('global_repository_id')->nullable();
            $table->unsignedBigInteger('global_archive_id')->nullable();
            $table->unsignedBigInteger('global_collection_id')->nullable();
            $table->string('signature')->nullable();
            $table->string('type')->nullable();
            $table->string('preservation')->nullable();
            $table->string('copy')->nullable();
            $table->string('l_number')->nullable();
            $table->text('manifestation_notes')->nullable();
            $table->text('location_note')->nullable();
            $table->timestamps();
        });

        Schema::create('global_places', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('country')->nullable();
            $table->string('division')->nullable();
            $table->json('alternative_names')->nullable();
        });

        Schema::create('global_keywords', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->json('name')->nullable();
        });

        Schema::create('ocr_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('tenant_prefix')->nullable();
            $table->unsignedBigInteger('letter_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('provider', 50);
            $table->string('model', 100);
            $table->string('status', 30)->default('success');
            $table->json('source_files')->nullable();
            $table->longText('recognized_text')->nullable();
            $table->json('metadata')->nullable();
            $table->json('mapped_fields')->nullable();
            $table->text('request_prompt')->nullable();
            $table->text('raw_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->unsignedBigInteger('applied_by_user_id')->nullable();
            $table->string('apply_mode', 30)->nullable();
            $table->json('applied_field_keys')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('ocr_snapshots');
        Schema::dropIfExists('global_keywords');
        Schema::dropIfExists('global_places');
        Schema::connection('tenant')->dropIfExists('test__manifestations');
        Schema::connection('tenant')->dropIfExists('test__test__keyword_letter');
        Schema::connection('tenant')->dropIfExists('test__keyword_letter');
        Schema::connection('tenant')->dropIfExists('test__letter_place');
        Schema::connection('tenant')->dropIfExists('test__test__keywords');
        Schema::connection('tenant')->dropIfExists('test__keywords');
        Schema::connection('tenant')->dropIfExists('test__places');
        Schema::connection('tenant')->dropIfExists('test__locations');
        Schema::connection('tenant')->dropIfExists('test__letter_user');
        Schema::connection('tenant')->dropIfExists('test__identity_letter');
        Schema::connection('tenant')->dropIfExists('test__identities');
        Schema::connection('tenant')->dropIfExists('test__letters');
        Schema::connection('tenant')->dropIfExists('test__users');
        tenancy()->end();
        @unlink($this->databasePath);

        parent::tearDown();
    }

    protected function createLetter(array $attributes = []): Letter
    {
        return Letter::create(array_merge([
            'date_year' => 2026,
            'date_month' => 4,
            'date_day' => 13,
            'status' => 'draft',
            'languages' => 'Czech;Latin',
            'notes_private' => 'Seed note',
            'content' => 'Seed content',
        ], $attributes));
    }

    public function test_duplicate_starts_with_exact_custom_history_line_and_not_inherited_history(): void
    {
        $user = User::withoutEvents(fn () => User::factory()->create(['name' => 'Admin Testovaci']));
        $this->actingAs($user);

        $source = $this->createLetter([
            'history' => "2026-04-08 15:33:21 – Simon Antos\n2026-04-09 10:00:00 – Admin Testovaci",
        ]);

        $controller = new class(app(LetterService::class)) extends LetterController {
            protected function duplicateRelatedEntities(Letter $sourceLetter, Letter $duplicatedLetter)
            {
                // Relation copying is not relevant for this history-focused test.
            }
        };

        $controller->duplicate(Request::create('/letters/' . $source->id . '/duplicate', 'GET'), $source);

        $duplicate = Letter::query()->whereKeyNot($source->id)->latest('id')->firstOrFail();

        $this->assertSame(
            'Admin Testovaci: dupli. #' . $source->id,
            preg_replace('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} – /', '', (string) $duplicate->history)
        );
        $this->assertStringNotContainsString('Simon Antos', (string) $duplicate->history);
        $this->assertStringNotContainsString("2026-04-09 10:00:00 – Admin Testovaci", (string) $duplicate->history);
        $this->assertSame(1, substr_count((string) $duplicate->history, "\n") + 1);
    }

    public function test_duplicate_of_duplicate_references_immediate_source_id(): void
    {
        $user = User::withoutEvents(fn () => User::factory()->create(['name' => 'Admin Testovaci']));
        $this->actingAs($user);

        $source = $this->createLetter([
            'history' => '2026-04-08 15:33:21 – Simon Antos',
        ]);

        $controller = new class(app(LetterService::class)) extends LetterController {
            protected function duplicateRelatedEntities(Letter $sourceLetter, Letter $duplicatedLetter)
            {
            }
        };

        $controller->duplicate(Request::create('/letters/' . $source->id . '/duplicate', 'GET'), $source);
        $firstDuplicate = Letter::query()->whereKeyNot($source->id)->latest('id')->firstOrFail();

        $controller->duplicate(Request::create('/letters/' . $firstDuplicate->id . '/duplicate', 'GET'), $firstDuplicate);
        $secondDuplicate = Letter::query()
            ->whereNotIn('id', [$source->id, $firstDuplicate->id])
            ->latest('id')
            ->firstOrFail();

        $this->assertStringEndsWith(': dupli. #' . $firstDuplicate->id, (string) $secondDuplicate->history);
    }

    public function test_regular_create_and_update_still_append_history_via_observer(): void
    {
        $user = User::withoutEvents(fn () => User::factory()->create(['name' => 'Admin Testovaci']));
        $this->actingAs($user);

        $letter = $this->createLetter([
            'history' => null,
        ]);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2} – Admin Testovaci$/',
            (string) $letter->history
        );

        $originalHistory = $letter->history;

        $letter->update([
            'notes_private' => 'Updated note',
        ]);

        $freshLetter = $letter->fresh();

        $this->assertStringStartsWith($originalHistory . "\n", (string) $freshLetter->history);
        $this->assertSame(2, substr_count((string) $freshLetter->history, "\n") + 1);
    }

    public function test_duplicate_copies_manifestations_to_new_letter(): void
    {
        $user = User::withoutEvents(fn () => User::factory()->create(['name' => 'Admin Testovaci']));
        $this->actingAs($user);

        $repository = Location::create([
            'name' => 'Repository A',
            'type' => 'repository',
        ]);
        $archive = Location::create([
            'name' => 'Archive A',
            'type' => 'archive',
        ]);
        $collection = Location::create([
            'name' => 'Collection A',
            'type' => 'collection',
        ]);

        $source = $this->createLetter();

        Manifestation::create([
            'letter_id' => $source->id,
            'repository_id' => $repository->id,
            'archive_id' => $archive->id,
            'collection_id' => $collection->id,
            'signature' => 'SIG-42',
            'type' => 'original',
            'preservation' => 'good',
            'copy' => 'manuscript',
            'l_number' => 'L-42',
            'manifestation_notes' => 'Copied manifestation notes',
            'location_note' => 'Copied location note',
        ]);

        $controller = new class(app(LetterService::class)) extends LetterController {
            protected function duplicateRelatedEntities(Letter $sourceLetter, Letter $duplicatedLetter)
            {
                // Keep the test focused on manifestation duplication.
            }
        };

        $controller->duplicate(Request::create('/letters/' . $source->id . '/duplicate', 'GET'), $source);

        $duplicate = Letter::query()->whereKeyNot($source->id)->latest('id')->firstOrFail();
        $manifestations = $duplicate->manifestations()->get();

        $this->assertCount(1, $manifestations);

        $manifestation = $manifestations->first();

        $this->assertSame($repository->id, $manifestation->repository_id);
        $this->assertSame($archive->id, $manifestation->archive_id);
        $this->assertSame($collection->id, $manifestation->collection_id);
        $this->assertSame('SIG-42', $manifestation->signature);
        $this->assertSame('original', $manifestation->type);
        $this->assertSame('good', $manifestation->preservation);
        $this->assertSame('manuscript', $manifestation->copy);
        $this->assertSame('L-42', $manifestation->l_number);
        $this->assertSame('Copied manifestation notes', $manifestation->manifestation_notes);
        $this->assertSame('Copied location note', $manifestation->location_note);
        $this->assertSame($duplicate->id, $manifestation->letter_id);
    }

    public function test_duplicate_copies_global_destinations_to_new_letter(): void
    {
        $user = User::withoutEvents(fn () => User::factory()->create(['name' => 'Admin Testovaci']));
        $this->actingAs($user);

        $destination = GlobalPlace::create([
            'name' => 'Vienna',
            'country' => 'Austria',
        ]);

        $source = $this->createLetter();
        $source->globalPlaces()->attach($destination->id, [
            'role' => 'destination',
            'position' => 0,
            'marked' => 'Wien',
        ]);

        $controller = app(LetterController::class);
        $controller->duplicate(Request::create('/letters/' . $source->id . '/duplicate', 'GET'), $source);

        $duplicate = Letter::query()->whereKeyNot($source->id)->latest('id')->firstOrFail();
        $duplicatedDestinations = $duplicate->globalDestinations()->get();

        $this->assertCount(1, $duplicatedDestinations);
        $this->assertSame($destination->id, $duplicatedDestinations->first()->id);
        $this->assertSame('Wien', $duplicatedDestinations->first()->pivot->marked);
    }

    public function test_duplicate_copies_ocr_snapshots_to_new_letter_and_resets_apply_audit(): void
    {
        $user = User::withoutEvents(fn () => User::factory()->create([
            'name' => 'Admin Testovaci',
            'email' => 'admin@example.com',
        ]));
        $this->actingAs($user);

        $source = $this->createLetter();

        OcrSnapshot::create([
            'tenant_id' => 1,
            'tenant_prefix' => 'test',
            'letter_id' => $source->id,
            'user_id' => $user->id,
            'user_email' => $user->email,
            'provider' => 'openai',
            'model' => 'gpt-4o',
            'status' => 'success',
            'source_files' => ['scan-1.jpg'],
            'recognized_text' => 'Recognized text',
            'metadata' => ['date' => '1901-02-03'],
            'mapped_fields' => ['date_year' => 1901, 'content' => 'Recognized text'],
            'request_prompt' => 'Prompt body',
            'raw_response' => 'Raw response',
            'error_message' => null,
            'applied_at' => now(),
            'applied_by_user_id' => $user->id,
            'apply_mode' => 'overwrite',
            'applied_field_keys' => ['content'],
        ]);

        $controller = app(LetterController::class);
        $controller->duplicate(Request::create('/letters/' . $source->id . '/duplicate', 'GET'), $source);

        $duplicate = Letter::query()->whereKeyNot($source->id)->latest('id')->firstOrFail();
        $snapshots = OcrSnapshot::query()
            ->where('letter_id', $duplicate->id)
            ->where('tenant_prefix', 'test')
            ->get();

        $this->assertCount(1, $snapshots);

        $snapshot = $snapshots->first();

        $this->assertSame('Recognized text', $snapshot->recognized_text);
        $this->assertSame(['date' => '1901-02-03'], $snapshot->metadata);
        $this->assertSame(['date_year' => 1901, 'content' => 'Recognized text'], $snapshot->mapped_fields);
        $this->assertSame(['scan-1.jpg'], $snapshot->source_files);
        $this->assertNull($snapshot->applied_at);
        $this->assertNull($snapshot->applied_by_user_id);
        $this->assertNull($snapshot->apply_mode);
        $this->assertNull($snapshot->applied_field_keys);
    }
}
