<?php

namespace App\Http\Traits;

use Illuminate\Support\Str;

trait MenuTrait
{
    public function getMenu(): array
    {
        $items = [
            [
                'route' => 'letters',
                'name' => __('hiko.letters'),
                'icon' => 'icons.mail-open',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'identities',
                'name' => __('hiko.identities'),
                'icon' => 'icons.user-group',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'places',
                'name' => __('hiko.places'),
                'icon' => 'icons.location-marker',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'keywords',
                'name' => __('hiko.keywords'),
                'icon' => 'icons.annotation',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'professions',
                'name' => __('hiko.professions'),
                'icon' => 'icons.academic-cap',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'locations',
                'name' => __('hiko.locations'),
                'icon' => 'icons.archive',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'users',
                'name' => __('hiko.users'),
                'icon' => 'icons.user-add',
                'ability' => 'manage-users',
            ],
            [
                'route' => 'compare-letters.index',
                'name' => __('hiko.compare_letters_comparision'),
                'icon' => 'icons.git-compare',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'images.upload',
                'name' => __('hiko.upload_image'),
                'icon' => 'icons.git-compare',
                'ability' => 'view-metadata',
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
