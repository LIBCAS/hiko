<?php

namespace Tests\Unit\Api\V2;

use App\Http\Requests\GlobalIdentityRequest;
use App\Http\Requests\IdentityRequest;
use App\Http\Resources\IdentityResource;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IdentityApiShapeTest extends TestCase
{
    public function test_identity_resource_exposes_unified_professions_and_scoped_global_identity(): void
    {
        $identity = new class {
            public int $id = 1335;
            public string $name = 'Tester, Local';
            public string $surname = 'Tester';
            public string $forename = 'Local';
            public string $type = 'person';
            public ?string $nationality = 'czech';
            public ?string $gender = 'M';
            public ?string $birth_year = '1900';
            public ?string $death_year = '1980';
            public ?string $viaf_id = '123456';
            public ?string $note = 'Note';
            public array $alternative_names = [];
            public array $related_names = [];
            public int $global_identity_id = 1;
            public object $globalIdentity;
            public Collection $localProfessions;
            public Collection $globalProfessions;
            public Collection $religions;
            public string $created_at = '2026-02-18T10:00:00.000000Z';
            public string $updated_at = '2026-02-18T10:00:00.000000Z';

            public function __construct()
            {
                $this->globalIdentity = (object) [
                    'id' => 1,
                    'name' => 'Tester, Global',
                    'type' => 'person',
                    'birth_year' => '1900',
                    'death_year' => '1980',
                ];
                $this->localProfessions = collect([$this->makeProfession(22, ['cs' => 'Mistni profese', 'en' => 'Local profession'], 82)]);
                $this->globalProfessions = collect([$this->makeProfession(394, ['cs' => 'Globalni profese', 'en' => 'Global profession'], 35)]);
                $this->religions = collect([(object) ['id' => 14, 'name' => 'Christianity', 'is_active' => 1]]);
            }

            public function relationLoaded($relation): bool
            {
                return in_array($relation, ['globalIdentity', 'localProfessions', 'globalProfessions', 'religions'], true);
            }

            private function makeProfession(int $id, array $name, ?int $categoryId): object
            {
                return new class($id, $name, $categoryId) {
                    public int $id;
                    public ?int $profession_category_id;
                    private array $attributes;

                    public function __construct(int $id, array $name, ?int $categoryId)
                    {
                        $this->id = $id;
                        $this->profession_category_id = $categoryId;
                        $this->attributes = ['name' => json_encode($name, JSON_UNESCAPED_UNICODE)];
                    }

                    public function getAttributes(): array
                    {
                        return $this->attributes;
                    }
                };
            }
        };

        $data = (new IdentityResource($identity))->toArray(IlluminateRequest::create('/api/v2/identity/1335', 'GET'));

        $this->assertSame('global-1', $data['global_identity']['reference']);
        $this->assertCount(2, $data['professions']);
        $this->assertSame('local-22', $data['professions'][0]['reference']);
        $this->assertSame('global-394', $data['professions'][1]['reference']);
        $this->assertArrayNotHasKey('global_professions', $data);
    }

    public function test_identity_request_normalizes_unified_and_legacy_identity_inputs(): void
    {
        $base = IlluminateRequest::create('/api/v2/identities', 'POST', [
            'type' => 'person',
            'surname' => 'Tester',
            'forename' => 'Local',
            'professions' => [
                ['id' => 22, 'scope' => 'local', 'reference' => 'global-999'],
                ['id' => 394, 'scope' => 'global', 'reference' => 'local-999'],
            ],
            'global_identity' => [
                'id' => 1,
                'scope' => 'global',
                'reference' => 'global-999',
            ],
        ]);

        $request = TestIdentityRequest::createFromBase($base);
        $request->runPrepareForValidation();

        $this->assertSame('local-22', $request->input('professions.0.id'));
        $this->assertSame('global-394', $request->input('professions.1.id'));
        $this->assertSame(1, $request->input('global_identity_id'));

        $legacyBase = IlluminateRequest::create('/api/v2/identities', 'POST', [
            'type' => 'person',
            'surname' => 'Tester',
            'forename' => 'Local',
            'local_professions' => [22],
            'global_professions' => [394],
            'global_identity_id' => 1,
        ]);

        $legacyRequest = TestIdentityRequest::createFromBase($legacyBase);
        $legacyRequest->runPrepareForValidation();

        $this->assertSame('local-22', $legacyRequest->input('professions.0.id'));
        $this->assertSame('global-394', $legacyRequest->input('professions.1.id'));
        $this->assertSame(1, $legacyRequest->input('global_identity_id'));
    }

    public function test_global_identity_request_accepts_scoped_profession_objects(): void
    {
        $base = IlluminateRequest::create('/api/v2/global-identities', 'POST', [
            'type' => 'person',
            'surname' => 'Tester',
            'forename' => 'Global',
            'professions' => [
                ['id' => 394, 'scope' => 'global', 'reference' => 'global-999'],
            ],
        ]);

        $request = TestGlobalIdentityRequest::createFromBase($base);
        $request->runPrepareForValidation();

        $this->assertSame([394], $request->input('professions'));
    }

    public function test_identity_put_allows_partial_updates_and_preserves_omitted_relation_inputs(): void
    {
        $request = TestIdentityRequest::createFromBase(IlluminateRequest::create('/api/v2/identity/1335', 'PUT', [
            'note' => 'Updated note only',
            'client_meta' => ['external_id' => 'abc-123'],
        ]));

        $request->runPrepareForValidation();

        $this->assertFalse(array_key_exists('professions', $request->all()));
        $this->assertFalse(array_key_exists('global_identity_id', $request->all()));

        $errors = $this->validateIdentityRequest($request);

        $this->assertSame([], $errors);
    }

    public function test_identity_request_rejects_unknown_top_level_fields(): void
    {
        $request = TestIdentityRequest::createFromBase(IlluminateRequest::create('/api/v2/identities', 'POST', [
            'type' => 'person',
            'surname' => 'Tester',
            'forename' => 'Local',
            'unexpected_field' => 'nope',
        ]));

        $errors = $this->validateIdentityRequest($request);

        $this->assertArrayHasKey('unexpected_field', $errors);
    }

    private function validateIdentityRequest(TestIdentityRequest $request): array
    {
        $request->runPrepareForValidation();

        $validator = Validator::make($request->all(), [
            'type' => ['nullable', 'string'],
            'surname' => ['nullable', 'string'],
            'forename' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'client_meta' => ['nullable', 'array'],
        ]);
        $request->withValidator($validator);

        return $validator->errors()->toArray();
    }
}

class TestIdentityRequest extends IdentityRequest
{
    public function runPrepareForValidation(): void
    {
        $this->prepareForValidation();
    }
}

class TestGlobalIdentityRequest extends GlobalIdentityRequest
{
    public function runPrepareForValidation(): void
    {
        $this->prepareForValidation();
    }
}
