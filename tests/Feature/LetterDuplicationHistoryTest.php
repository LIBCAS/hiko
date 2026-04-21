<?php

namespace Tests\Feature;

use App\Http\Controllers\LetterController;
use App\Models\Letter;
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
            'id' => 'test-tenant',
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
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('letter_id');
            $table->string('role', 100)->nullable();
            $table->integer('position')->nullable();
            $table->text('marked')->nullable();
            $table->text('salutation')->nullable();
        });

        Schema::connection('tenant')->create('test__letter_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('letter_id');
            $table->unsignedBigInteger('user_id');
            $table->unique(['letter_id', 'user_id']);
        });
    }

    protected function tearDown(): void
    {
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
}
