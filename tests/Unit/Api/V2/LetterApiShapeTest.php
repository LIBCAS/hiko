<?php

namespace Tests\Unit\Api\V2;

use App\Http\Requests\Api\v2\LetterRequest;
use App\Http\Controllers\Api\v2\LetterController;
use App\Http\Resources\LetterResource;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LetterApiShapeTest extends TestCase
{
    public function test_letter_resource_flattens_role_arrays_and_exposes_letter_flags_top_level(): void
    {
        $letter = new class {
            public int $id = 4069;
            public string $name = 'Test letter';
            public string $uuid = '07032d70-f4e1-4c5f-b8bc-124f5d3ea5b5';
            public string $pretty_date = '13. 9. 1933';
            public string $pretty_range_date = '21. 3. 1939';
            public ?int $date_year = 1933;
            public ?int $date_month = 9;
            public ?int $date_day = 13;
            public string $date_marked = '13.09.1933';
            public bool $date_uncertain = false;
            public bool $date_approximate = true;
            public bool $date_inferred = false;
            public bool $date_is_range = true;
            public string $date_note = 'Date note';
            public ?int $range_year = 1939;
            public ?int $range_month = 3;
            public ?int $range_day = 21;
            public bool $author_inferred = false;
            public bool $author_uncertain = false;
            public string $author_note = 'Author note';
            public bool $recipient_inferred = true;
            public bool $recipient_uncertain = false;
            public string $recipient_note = 'Recipient note';
            public bool $origin_inferred = true;
            public bool $origin_uncertain = false;
            public string $origin_note = 'Origin note';
            public bool $destination_inferred = false;
            public bool $destination_uncertain = true;
            public string $destination_note = 'Destination note';
            public string $people_mentioned_note = 'Mentioned note';
            public array $copies = [];
            public string $incipit = 'Incipit';
            public string $explicit = 'Explicit';
            public string $languages = 'Arabic;Azerbaijani';
            public string $notes_public = 'Public note';
            public string $content = '<p>Content</p>';
            public string $copyright = 'Copyright';
            public Collection $authors;
            public Collection $recipients;
            public Collection $mentioned;
            public Collection $globalAuthors;
            public Collection $globalRecipients;
            public Collection $globalMentioned;
            public Collection $origins;
            public Collection $destinations;
            public Collection $globalOrigins;
            public Collection $globalDestinations;
            public Collection $localKeywords;
            public Collection $globalKeywords;

            public function __construct()
            {
                $this->authors = collect([$this->makeRoleItem(2483, 'Local Person, Author', 'Author mark')]);
                $this->recipients = collect();
                $this->mentioned = collect([$this->makeRoleItem(2484, 'Local Person, Mentioned', null)]);
                $this->globalAuthors = collect();
                $this->globalRecipients = collect([$this->makeRoleItem(18, 'Global Person, Recipient', 'Recipient mark', 'Dear recipient')]);
                $this->globalMentioned = collect([$this->makeRoleItem(18, 'Global Person, Mentioned', null)]);
                $this->origins = collect([$this->makeRoleItem(181, 'Local origin', 'Origin mark')]);
                $this->destinations = collect();
                $this->globalOrigins = collect();
                $this->globalDestinations = collect([$this->makeRoleItem(238, 'Global destination', 'Destination mark')]);
                $this->localKeywords = collect([$this->makeKeywordItem(74, ['cs' => 'Mistni klicove slovo', 'en' => 'Local keyword'])]);
                $this->globalKeywords = collect([$this->makeKeywordItem(10442, ['cs' => 'Global klicove slovo', 'en' => 'Global keyword'])]);
            }

            public function getAttributes(): array
            {
                return [
                    'related_resources' => '[{"title":"Source 1","link":"https://example.org/source-1"}]',
                    'abstract' => '{"cs":"Abstrakt","en":"Abstract"}',
                ];
            }

            public function getMedia(): Collection
            {
                return collect();
            }

            private function makeRoleItem(int $id, string $name, ?string $marked, ?string $salutation = null): object
            {
                return new class($id, $name, $marked, $salutation) {
                    public int $id;
                    public string $name;
                    public object $pivot;

                    public function __construct(int $id, string $name, ?string $marked, ?string $salutation)
                    {
                        $this->id = $id;
                        $this->name = $name;
                        $this->pivot = (object) [
                            'marked' => $marked,
                            'salutation' => $salutation,
                        ];
                    }
                };
            }

            private function makeKeywordItem(int $id, array $name): object
            {
                return new class($id, $name) {
                    public int $id;
                    private array $attributes;

                    public function __construct(int $id, array $name)
                    {
                        $this->id = $id;
                        $this->attributes = ['name' => json_encode($name, JSON_UNESCAPED_UNICODE)];
                    }

                    public function getAttributes(): array
                    {
                        return $this->attributes;
                    }
                };
            }
        };

        $resource = new LetterResource($letter);
        $data = $resource->toArray(IlluminateRequest::create('/api/v2/letter/4069', 'GET'));

        $this->assertIsArray($data['authors']);
        $this->assertSame('local-2483', $data['authors'][0]['reference']);
        $this->assertArrayNotHasKey('items', $data['authors']);
        $this->assertSame('Global Person, Recipient', $data['recipients'][0]['name']);
        $this->assertTrue($data['recipient_inferred']);
        $this->assertSame('Recipient note', $data['recipient_note']);
        $this->assertSame('Mentioned note', $data['people_mentioned_note']);
        $this->assertSame('global-10442', $data['keywords'][1]['reference']);
        $this->assertSame(['Arabic', 'Azerbaijani'], $data['languages']);
    }

    public function test_letter_request_normalizes_unified_and_legacy_inputs_into_scoped_references(): void
    {
        $base = IlluminateRequest::create('/api/v2/letters', 'POST', [
            'authors' => [
                ['id' => 12, 'scope' => 'local', 'reference' => 'global-999', 'marked' => 'Author mark'],
            ],
            'recipients' => [
                ['id' => 18, 'scope' => 'global', 'reference' => 'local-999', 'marked' => 'Recipient mark', 'salutation' => 'Dear recipient'],
            ],
            'mentioned' => [
                ['id' => 19, 'scope' => 'global', 'reference' => 'local-999'],
            ],
            'local_origins' => [
                ['id' => 3, 'marked' => 'Origin mark'],
            ],
            'global_destinations' => [
                ['id' => 9, 'marked' => 'Destination mark'],
            ],
            'local_keywords' => [74],
            'global_keywords' => [10442],
            'copies' => [
                [
                    'repository' => ['id' => 25, 'scope' => 'local', 'reference' => 'global-999'],
                    'archive' => ['id' => 9, 'scope' => 'global', 'reference' => 'local-999'],
                    'collection' => ['value' => 'local-26'],
                ],
            ],
        ]);

        $request = TestLetterRequest::createFromBase($base);
        $request->runPrepareForValidation();

        $this->assertSame('local-12', $request->input('authors.0.id'));
        $this->assertSame('global-18', $request->input('recipients.0.id'));
        $this->assertSame('global-19', $request->input('mentioned.0.id'));
        $this->assertSame('local-3', $request->input('origins.0.id'));
        $this->assertSame('global-9', $request->input('destinations.0.id'));
        $this->assertSame('local-74', $request->input('keywords.0.id'));
        $this->assertSame('global-10442', $request->input('keywords.1.id'));
        $this->assertSame('local-25', $request->input('copies.0.repository'));
        $this->assertSame('global-9', $request->input('copies.0.archive'));
        $this->assertSame('local-26', $request->input('copies.0.collection'));
    }

    public function test_letter_put_allows_partial_updates_without_requiring_omitted_fields(): void
    {
        $request = TestLetterRequest::createFromBase(IlluminateRequest::create('/api/v2/letter/4069', 'PUT', [
            'origin_note' => null,
            'client_meta' => ['external_id' => 'abc-123'],
        ]));

        $errors = $this->validateRequest($request);

        $this->assertSame([], $errors);
    }

    public function test_letter_request_rejects_unknown_top_level_fields(): void
    {
        $request = TestLetterRequest::createFromBase(IlluminateRequest::create('/api/v2/letters', 'POST', [
            'date_uncertain' => false,
            'date_approximate' => false,
            'date_inferred' => false,
            'date_is_range' => false,
            'author_uncertain' => false,
            'author_inferred' => false,
            'recipient_uncertain' => false,
            'recipient_inferred' => false,
            'destination_uncertain' => false,
            'destination_inferred' => false,
            'origin_uncertain' => false,
            'origin_inferred' => false,
            'unexpected_field' => 'nope',
        ]));

        $errors = $this->validateRequest($request);

        $this->assertArrayHasKey('unexpected_field', $errors);
    }

    public function test_letter_controller_accepts_unified_mentioned_objects(): void
    {
        $controller = new TestApiV2LetterController(app(\App\Services\LetterService::class));

        $data = $controller->exposePrepareMentionedIdentityAttachmentData([
            ['id' => 'local-12'],
            ['id' => 'global-18'],
        ]);

        $this->assertSame([
            'local' => [
                12 => [
                    'position' => 0,
                    'role' => 'mentioned',
                ],
            ],
            'global' => [
                18 => [
                    'position' => 1,
                    'role' => 'mentioned',
                ],
            ],
        ], $data);
    }

    private function validateRequest(TestLetterRequest $request): array
    {
        $request->runPrepareForValidation();

        $validator = Validator::make($request->all(), $request->rules());
        $request->withValidator($validator);

        return $validator->errors()->toArray();
    }
}

class TestLetterRequest extends LetterRequest
{
    public function runPrepareForValidation(): void
    {
        $this->prepareForValidation();
    }
}

class TestApiV2LetterController extends LetterController
{
    public function exposePrepareMentionedIdentityAttachmentData(?array $items): array
    {
        return $this->prepareMentionedIdentityAttachmentData($items);
    }
}
