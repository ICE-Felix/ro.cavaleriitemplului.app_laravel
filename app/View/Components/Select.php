<?php

namespace App\View\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\View\Component;

class Select extends Component
{
    public String $name;
    public array $options;
    public String|null $selected;
    public String|null $error;
    public String|null $success;
    public String|null $label;


    public function __construct($name,  $label = null, $options = [], $selected = null, $error = null, $success = null)
    {
        $this->name = $name;
        $this->label = $label;
        $this->options = $options;
        $this->selected = $selected;
        $this->error = $error;
        $this->success = $success;
    }

    public function render(): Factory|Application|View|Htmlable|string|\Closure|\Illuminate\Contracts\Foundation\Application
    {
        return view('components.select');
    }
}
