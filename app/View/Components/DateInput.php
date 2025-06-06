<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Carbon\Carbon;

class DateInput extends Component
{
    public $name;
    public $label;
    public $placeholder;
    public $error;
    public $value;
    public $min;
    public $max;

    public function __construct(
        $name,
        $label = '',
        $placeholder = null,
        $error = null,
        $value = '',
        $min = null,
        $max = null
    ) {
        $this->name = $name;
        $this->label = $label;
        $this->placeholder = $placeholder;
        $this->error = $error;

        // Format the value to 'Y-m-d' if it's a valid date
        $this->value = $value ? Carbon::parse($value)->format('Y-m-d') : '';

        // Format min and max dates if provided
        $this->min = $min ? Carbon::parse($min)->format('Y-m-d') : null;
        $this->max = $max ? Carbon::parse($max)->format('Y-m-d') : null;
    }

    public function render()
    {
        return view('components.date-input');
    }
}
