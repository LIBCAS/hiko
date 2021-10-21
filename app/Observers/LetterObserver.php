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
        $letter->date_computed = $this->computeDate($letter);
    }

    public function created(Letter $letter)
    {
        $user = Auth::user();
        $user = $user ? $user : User::first();
        $letter->users()->attach($user->id);
    }

    public function updating(Letter $letter)
    {
        $user = Auth::user();
        $letter->history = $letter->history . date('Y-m-d H:i:s') . ' – ' . $user->name . "\n";
        $letter->date_computed = $this->computeDate($letter);
        $letter->users()->syncWithoutDetaching($user->id);
    }

    public function saved(Letter $letter)
    {
        $letter->authors()->each(function ($author) {
            $this->regenerateNames($author);
        });

        $letter->recipients()->each(function ($recipient) {
            $this->regenerateNames($recipient);
        });
    }

    protected function computeDate($letter)
    {
        $dates = [
            $letter->date_year ? (string) $letter->date_year : '0001',
            $letter->date_month ? str_pad($letter->date_month, 2, '0', STR_PAD_LEFT) : '01',
            $letter->date_day ? str_pad($letter->date_day, 2, '0', STR_PAD_LEFT) : '01',
        ];

        return implode('-', $dates);
    }

    protected function regenerateNames($identity)
    {
        $names = $identity->letters
            ->map(function ($letter) {
                return $letter->pivot->marked;
            })
            ->reject(function ($marked) {
                return empty($marked);
            })
            ->unique()
            ->toArray();

        $identity->alternative_names = $names;
        $identity->save();
    }
}
