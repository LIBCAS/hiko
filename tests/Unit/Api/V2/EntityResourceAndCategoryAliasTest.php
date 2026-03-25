<?php

namespace Tests\Unit\Api\V2;

use App\Http\Requests\GlobalKeywordRequest;
use App\Http\Requests\GlobalProfessionRequest;
use App\Http\Requests\KeywordRequest;
use App\Http\Requests\ProfessionRequest;
use App\Http\Resources\KeywordResource;
use App\Http\Resources\LocationResource;
use App\Http\Resources\PlaceResource;
use App\Http\Resources\ProfessionResource;
use App\Models\GlobalKeyword;
use App\Models\GlobalLocation;
use App\Models\GlobalPlace;
use App\Models\GlobalProfession;
use Illuminate\Http\Request as IlluminateRequest;
use Tests\TestCase;

class EntityResourceAndCategoryAliasTest extends TestCase
{
    public function test_place_and_location_resources_expose_scope_and_reference(): void
    {
        $localPlace = new class {
            public int $id = 3;
            public string $name = 'Local Place';
            public string $country = 'Czech Republic';
            public ?string $division = 'Bohemia';
            public ?string $note = null;
            public ?float $latitude = null;
            public ?float $longitude = null;
            public ?int $geoname_id = null;
            public array $alternative_names = [];
        };

        $globalPlace = new class extends GlobalPlace {
            public int $id = 9;
            public string $name = 'Global Place';
            public string $country = 'Czech Republic';
            public ?string $division = 'Moravia';
            public ?string $note = null;
            public ?float $latitude = null;
            public ?float $longitude = null;
            public ?int $geoname_id = null;
            public array $alternative_names = [];
        };

        $localLocation = new class {
            public int $id = 25;
            public string $name = 'Repository';
            public string $type = 'repository';
        };

        $globalLocation = new class extends GlobalLocation {
            public int $id = 9;
            public string $name = 'Global Archive';
            public string $type = 'archive';
        };

        $request = IlluminateRequest::create('/api/v2/test', 'GET');

        $this->assertSame('local-3', (new PlaceResource($localPlace))->toArray($request)['reference']);
        $this->assertSame('global-9', (new PlaceResource($globalPlace))->toArray($request)['reference']);
        $this->assertSame('local-25', (new LocationResource($localLocation))->toArray($request)['reference']);
        $this->assertSame('global-9', (new LocationResource($globalLocation))->toArray($request)['reference']);
    }

    public function test_keyword_and_profession_resources_expose_scope_and_reference(): void
    {
        $localKeyword = new class {
            public int $id = 74;
            public ?int $keyword_category_id = 64;
            public function getAttributes(): array
            {
                return ['name' => '{"cs":"Mistni klicove slovo","en":"Local keyword"}'];
            }
        };

        $globalKeyword = new class extends GlobalKeyword {
            public int $id = 10442;
            public ?int $keyword_category_id = 31;
            public function getAttributes(): array
            {
                return ['name' => '{"cs":"Global klicove slovo","en":"Global keyword"}'];
            }
        };

        $localProfession = new class {
            public int $id = 152;
            public ?int $profession_category_id = 82;
            public function getAttributes(): array
            {
                return ['name' => '{"cs":"Mistni profese","en":"Local profession"}'];
            }
        };

        $globalProfession = new class extends GlobalProfession {
            public int $id = 637;
            public ?int $profession_category_id = 35;
            public function getAttributes(): array
            {
                return ['name' => '{"cs":"Globalni profese","en":"Global profession"}'];
            }
        };

        $request = IlluminateRequest::create('/api/v2/test', 'GET');

        $this->assertSame('local-74', (new KeywordResource($localKeyword))->toArray($request)['reference']);
        $this->assertSame('global-10442', (new KeywordResource($globalKeyword))->toArray($request)['reference']);
        $this->assertSame('local-152', (new ProfessionResource($localProfession))->toArray($request)['reference']);
        $this->assertSame('global-637', (new ProfessionResource($globalProfession))->toArray($request)['reference']);
    }

    public function test_keyword_and_profession_requests_accept_category_id_aliases(): void
    {
        $keywordRequest = TestKeywordRequest::createFromBase(IlluminateRequest::create('/api/v2/keywords', 'POST', [
            'cs' => 'Keyword',
            'category_id' => 64,
        ]));
        $keywordRequest->runPrepareForValidation();
        $this->assertSame(64, $keywordRequest->input('keyword_category_id'));

        $globalKeywordRequest = TestGlobalKeywordRequest::createFromBase(IlluminateRequest::create('/api/v2/global-keywords', 'POST', [
            'cs' => 'Keyword',
            'category' => 31,
        ]));
        $globalKeywordRequest->runPrepareForValidation();
        $this->assertSame(31, $globalKeywordRequest->input('keyword_category_id'));

        $professionRequest = TestProfessionRequest::createFromBase(IlluminateRequest::create('/api/v2/professions', 'POST', [
            'cs' => 'Profession',
            'category_id' => 82,
        ]));
        $professionRequest->runPrepareForValidation();
        $this->assertSame(82, $professionRequest->input('profession_category_id'));

        $globalProfessionRequest = TestGlobalProfessionRequest::createFromBase(IlluminateRequest::create('/api/v2/global-professions', 'POST', [
            'cs' => 'Profession',
            'category' => 35,
        ]));
        $globalProfessionRequest->runPrepareForValidation();
        $this->assertSame(35, $globalProfessionRequest->input('profession_category_id'));
    }
}

class TestKeywordRequest extends KeywordRequest
{
    public function runPrepareForValidation(): void
    {
        $this->prepareForValidation();
    }
}

class TestGlobalKeywordRequest extends GlobalKeywordRequest
{
    public function runPrepareForValidation(): void
    {
        $this->prepareForValidation();
    }
}

class TestProfessionRequest extends ProfessionRequest
{
    public function runPrepareForValidation(): void
    {
        $this->prepareForValidation();
    }
}

class TestGlobalProfessionRequest extends GlobalProfessionRequest
{
    public function runPrepareForValidation(): void
    {
        $this->prepareForValidation();
    }
}
