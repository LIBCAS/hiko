<?php

namespace App\Http\Traits;

use Illuminate\Support\Str;

trait MenuTrait
{
    public function getMenu()
    {
        $items = [
            [
                'route' => 'dashboard',
                'name' => __('Dopisy'),
                'icon' => 'heroicon-o-mail-open',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'dashboard',
                'name' => __('Lidé a instituce'),
                'icon' => 'heroicon-o-user-group',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'places',
                'name' => __('Místa'),
                'icon' => 'heroicon-o-location-marker',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'keywords',
                'name' => __('Klíčová slova'),
                'icon' => 'heroicon-o-annotation',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'professions',
                'name' => __('Profese'),
                'icon' => 'heroicon-o-academic-cap',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'locations',
                'name' => __('Uložení'),
                'icon' => 'heroicon-o-archive',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'users',
                'name' => __('Uživatelé'),
                'icon' => 'heroicon-o-user-add',
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
