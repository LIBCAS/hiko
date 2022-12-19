<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

class RegenerateNames implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Collection $identites;

    public function __construct($identites)
    {
        $this->identites = $identites;
    }

    public function handle()
    {
        $this->identites
            ->each(function ($identity) {
                $identity->alternative_names = $identity->letters
                    ->map(function ($letter) {
                        return $letter->pivot->marked;
                    })
                    ->reject(function ($marked) {
                        return empty($marked);
                    })
                    ->unique()
                    ->toArray();

                $identity->save();
            });
    }
}
