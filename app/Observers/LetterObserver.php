<?php

namespace App\Observers;

use App\Models\Letter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LetterObserver
{
    /**
     * Runs when creating or updating a letter
     */
    public function saving(Letter $letter): void
    {
        if (empty($letter->uuid)) {
            $letter->uuid = (string) Str::uuid();
        }

        // If letter's date_is_range is false, clear range date fields
        if (!(bool) $letter->date_is_range) {
            $letter->range_year = null;
            $letter->range_month = null;
            $letter->range_day = null;
        }

        // Compute the canonical date for sorting
        $letter->date_computed = computeDate($letter);
    }

    /**
     * Post-persist hooks - attach user and append history
     */
    public function created(Letter $letter): void
    {
        $this->attachEditorAndHistory($letter, 'created');
    }

    public function updated(Letter $letter): void
    {
        $this->attachEditorAndHistory($letter, 'updated');
    }

    /**
     * Attach the current user (if any) and append a history line.
     * Uses saveQuietly() to avoid recursive model events.
     */
    protected function attachEditorAndHistory(Letter $letter, string $event): void
    {
        if ($letter->skipAutomaticHistory) {
            return;
        }

        $user = Auth::user();
        $name = $user?->name ?? 'system';

        // Append history line
        $timestamp = now()->format('Y-m-d H:i:s');
        $letter->history = rtrim((string) $letter->history) . ($letter->history ? "\n" : '') . "{$timestamp} – {$name}";
        $letter->saveQuietly(); // no events

        // Sync pivot without detaching (only if we actually have a user)
        if ($user) {
            $letter->users()->syncWithoutDetaching([$user->id]);
        } else {
            Log::warning('LetterObserver: no authenticated user when adding history/user pivot.');
        }
    }
}
