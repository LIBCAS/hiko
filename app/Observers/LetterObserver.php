<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Letter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LetterObserver
{
    public function creating(Letter $letter)
    {
        $user = Auth::user() ?: User::first();

        if (!$user) {
            Log::error('No valid user found while creating a letter.');
            throw new \Exception('No valid user found.');
        }

        $letter->uuid = Str::uuid();
        $letter->history = date('Y-m-d H:i:s') . ' – ' . $user->name . "\n";
        $letter->date_computed = computeDate($letter);
    }

    public function created(Letter $letter)
    {
        $user = Auth::user() ?: User::first();

        if ($user) {
            $letter->users()->attach($user->id);
        } else {
            Log::error('No valid user found for attaching to the letter.');
        }
    }
}
