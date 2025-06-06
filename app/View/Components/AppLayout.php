<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    public mixed $breadcrumbs;

    public function __construct($breadcrumbs = [])
    {
        $this->breadcrumbs = $breadcrumbs;
    }

    public function render(): View
    {
        return view('layouts.app', ['breadcrumbs' => $this->breadcrumbs]);
    }
}
