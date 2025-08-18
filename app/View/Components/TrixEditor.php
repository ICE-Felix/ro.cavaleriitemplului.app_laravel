<?php

namespace App\View\Components;

use Illuminate\View\Component;

class TrixEditor extends Component
{
    public $name;
    public $label;
    public $value;
    public $success;
    public $error;
    public $rows;
    public $placeholder;
    public $required;
    public $enableAI;

    public function __construct(
        $name, 
        $label = null, 
        $value = null, 
        $success = null, 
        $error = null, 
        $rows = null,
        $placeholder = null,
        $required = false,
        $enableAI = true
    ) {
        $this->name = $name;
        $this->label = $label ?? ucfirst($name);
        $this->value = $value;
        $this->success = $success;
        $this->error = $error;
        $this->rows = $rows ?? 5;
        $this->placeholder = $placeholder;
        $this->required = $required;
        $this->enableAI = $enableAI;
    }

    public function render()
    {
        return view('components.trix-editor');
    }
} 