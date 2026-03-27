<?php

namespace Tests\Unit;

use App\Models\Letter;
use App\Services\LetterService;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;

class LetterServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_sync_manifestations_accepts_null_copies_data(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $manifestationsRelation = Mockery::mock();
        $manifestationsRelation->shouldReceive('delete')->once();
        $manifestationsRelation->shouldReceive('create')->never();

        $letter = Mockery::mock(Letter::class);
        $letter->shouldReceive('manifestations')
            ->once()
            ->andReturn($manifestationsRelation);

        $service = new LetterService();

        $service->syncManifestations($letter, null);
    }
}
