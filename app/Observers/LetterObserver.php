<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Letter;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class LetterObserver
{
    public function creating(Letter $letter)
    {
        $user = Auth::user();
        $user = $user ? $user : User::first();
        $letter->uuid = Str::uuid();
        $letter->history = date('Y-m-d H:i:s') . ' – ' . $user->name . "\n";
        $letter->date_computed = computeDate($letter);
    }

    public function created(Letter $letter)
    {
        $user = Auth::user();
        $user = $user ? $user : User::first();
        $letter->users()->attach($user->id);
    }
}
