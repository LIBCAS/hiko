<?php

namespace Tests\Unit;

use App\Models\GlobalIdentity;
use App\Models\Identity;
use App\Services\GlobalIdentityMergeService;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class GlobalIdentityMergeServiceTest extends TestCase
{
    #[Test]
    public function it_does_not_match_people_by_surname_alone(): void
    {
        $service = new GlobalIdentityMergeService();
        $method = (new ReflectionClass($service))->getMethod('findBestGlobalMatchInCollection');
        $method->setAccessible(true);

        $local = new Identity([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
        ]);

        $global = new GlobalIdentity([
            'id' => 10332,
            'name' => 'Vrba',
            'surname' => 'Vrba',
            'forename' => null,
            'type' => 'person',
        ]);

        $result = $method->invoke(
            $service,
            $local,
            collect([$global]),
            ['name_similarity', 'type'],
            ['name_similarity_threshold' => 100]
        );

        $this->assertNull($result['global']);
    }

    #[Test]
    public function it_still_matches_people_by_full_name(): void
    {
        $service = new GlobalIdentityMergeService();
        $method = (new ReflectionClass($service))->getMethod('findBestGlobalMatchInCollection');
        $method->setAccessible(true);

        $local = new Identity([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
        ]);

        $global = new GlobalIdentity([
            'id' => 1,
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
        ]);

        $result = $method->invoke(
            $service,
            $local,
            collect([$global]),
            ['name_similarity', 'type'],
            ['name_similarity_threshold' => 100]
        );

        $this->assertSame($global, $result['global']);
    }
}
