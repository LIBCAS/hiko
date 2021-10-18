<?php

namespace App\Http\Traits;

use Illuminate\Support\Str;

trait MenuTrait
{
    public function getMenu()
    {
        $items = [
            [
                'route' => 'letters',
                'name' => __('Dopisy'),
                'icon' => 'icons.mail-open',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'identities',
                'name' => __('Lidé a instituce'),
                'icon' => 'icons.user-group',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'places',
                'name' => __('Místa'),
                'icon' => 'icons.location-marker',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'keywords',
                'name' => __('Klíčová slova'),
                'icon' => 'icons.annotation',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'professions',
                'name' => __('Profese'),
                'icon' => 'icons.academic-cap',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'locations',
                'name' => __('Uložení'),
                'icon' => 'icons.archive',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'users',
                'name' => __('Uživatelé'),
                'icon' => 'icons.user-add',
                'ability' => 'manage-users',
            ],
        ];

        return collect($items)->map(function ($item) {
            return $this->addStatus($item);
        })->toArray();
    }

    protected function addStatus($item)
    {
        $item['active'] = request()->routeIs($item['route']) || Str::startsWith(request()->route()->getName(), $item['route']);
        return $item;
    }
}
