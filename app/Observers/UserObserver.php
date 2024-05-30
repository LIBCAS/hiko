<?php

namespace App\Observers;

use App\Models\User;
use App\Jobs\UserCreated;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;

class UserObserver
{
    public function creating(User $user)
    {
        if (empty($user->password)) {
            $user->password = Hash::make(Str::random(60));
        }
    }

    public function created(User $user)
    {
        if (!App::environment('local')) {
            UserCreated::dispatch($user);
        }
    }
}
