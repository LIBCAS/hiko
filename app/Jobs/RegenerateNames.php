<?php

namespace App\Jobs;

use App\Models\Letter;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RegenerateNames implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $letter;

    public function __construct(Letter $letter)
    {
        $this->letter = $letter;
    }

    public function handle()
    {
        $this->letter->authors()->each(function ($author) {
            $this->regenerate($author);
        });

        $this->letter->recipients()->each(function ($recipient) {
            $this->regenerate($recipient);
        });
    }

    protected function regenerate($identity)
    {
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
    }
}
