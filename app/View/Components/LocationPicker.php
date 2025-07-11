<?php

namespace App\View\Components;

use Illuminate\View\Component;

class LocationPicker extends Component
{
    public $name;
    public $label;
    public $error;
    public $success;
    public $value;
    public $latitude;
    public $longitude;

    public function __construct(
        $name,
        $label = null,
        $error = null,
        $success = null,
        $value = null,
        $latitude = null,
        $longitude = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->error = $error;
        $this->success = $success;
        $this->value = $value;
        $this->latitude = $latitude ?? 44.4268;  // Default latitude for Bucharest
        $this->longitude = $longitude ?? 26.1025;  // Default longitude for Bucharest
    }

    public function render()
    {
        return view('components.location-picker');
    }
} 