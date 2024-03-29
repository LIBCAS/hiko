<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UsersImport
{
    /**
     * @throws FileNotFoundException
     */
    public function import(): string
    {
        if (!Storage::disk('local')->exists('imports/users.json')) {
            return 'Soubor neexistuje';
        }

        collect(json_decode(Storage::disk('local')->get('imports/users.json')))
            ->each(function ($user) {
                User::create([
                    'name' => $user->name,
                    'email' => $user->email,
                    'password' => Hash::make(bin2hex(openssl_random_pseudo_bytes(8))),
                    'role' => 'editor',
                ]);
            });

        return 'Import uživatelů byl úspěšný';
    }
}
