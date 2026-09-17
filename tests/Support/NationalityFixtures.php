<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use App\Support\NationalitySchema;

final class NationalityFixtures
{
    public static function create(array $sources): void
    {
        Schema::dropIfExists('nationalities');
        Schema::create('nationalities', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->timestamps();
        });

        foreach ([1 => ['česká', 'Czech'], 2 => ['maďarská', 'Hungarian'], 3 => ['slovenská', 'Slovak'], 4 => ['německá', 'German'], 5 => ['francouzská', 'French']] as $id => [$cs, $en]) {
            DB::table('nationalities')->insert(['id' => $id, 'name' => json_encode(['cs' => $cs, 'en' => $en])]);
        }

        foreach ($sources as $source) {
            $pivot = NationalitySchema::pivotName($source);
            Schema::dropIfExists($pivot);
            Schema::create($pivot, function (Blueprint $table) use ($source) {
                $key = $source === 'global_identities' ? 'global_identity_id' : 'identity_id';
                $table->unsignedBigInteger($key);
                $table->unsignedBigInteger('nationality_id');
                $table->unsignedInteger('position');
                $table->primary([$key, 'nationality_id']);
            });
        }
    }
}
