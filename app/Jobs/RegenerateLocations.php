<?php

namespace App\Jobs;

use App\Models\Letter;
use App\Models\Location;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RegenerateLocations implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $keys = ['repository', 'collection', 'archive'];

    public function handle()
    {
        Location::truncate();

        $data = $this->loadData();

        foreach ($this->keys as $key) {
            collect($data[$key])
                ->reject(function ($item) {
                    return empty($item);
                })
                ->unique()
                ->each(function ($item) use ($key) {
                    Location::create([
                        'name' => $item,
                        'type' => $key,
                    ]);
                });
        }
    }

    protected function loadData()
    {
        $result = [];

        Letter::select('copies')->get()
            ->reject(function ($item) {
                return empty($item->copies);
            })
            ->each(function ($letter) use (&$result) {
                foreach ($letter->copies as $copy) {
                    foreach ($this->keys as $key) {
                        if (isset($copy[$key])) {
                            $result[$key][] = $copy[$key];
                        }
                    }
                }
            })
            ->toArray();

        return $result;
    }
}
