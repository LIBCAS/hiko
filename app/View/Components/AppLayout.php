<?php

namespace App\View\Components;

use App\Http\Traits\MenuTrait;
use Illuminate\View\Component;

class AppLayout extends Component
{
    use MenuTrait;

    public $menuItems;
    public $title;

    public function __construct($title = '')
    {
        $this->title = $title;
        $this->menuItems = $this->getMenu();
    }

    public function render(): \Illuminate\View\View
    {
        return view('layouts.app');
    }
}
