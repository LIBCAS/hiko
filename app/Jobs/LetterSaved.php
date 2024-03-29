<?php

namespace App\Jobs;

use App\Models\Letter;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class LetterSaved implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Letter $letter;

    public function __construct(Letter $letter)
    {
        $this->letter = $letter;
    }

    public function handle()
    {
        $this->letter->history = $this->letter->history . date('Y-m-d H:i:s') . ' – ' . auth()->user()->name . "\n";
        $this->letter->date_computed = computeDate($this->letter);
        $this->letter->users()->syncWithoutDetaching(auth()->user()->id);

        $this->letter->saveQuietly();
    }
}
