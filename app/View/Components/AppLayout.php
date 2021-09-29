<?php

namespace App\View\Components;

use Illuminate\View\Component;

class AppLayout extends Component
{
    public $menuItems = [];
    public $title;

    public function __construct($title = '')
    {
        $this->title = $title;

        $this->menuItems = [
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
                'route' => 'dashboard',
                'name' => __('Místa'),
                'icon' => 'heroicon-o-location-marker',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'dashboard',
                'name' => __('Klíčová slova'),
                'icon' => 'heroicon-o-annotation',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'dashboard',
                'name' => __('Profese'),
                'icon' => 'heroicon-o-academic-cap',
                'ability' => 'view-metadata',
            ],
            [
                'route' => 'dashboard',
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
    }

    /**
     * Get the view / contents that represents the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('layouts.app');
    }
}
