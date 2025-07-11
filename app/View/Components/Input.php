<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Input extends Component
{
    public $name;
    public $label;
    public $placeholder;
    public $value;
    public $success;
    public $error;

    public function __construct($name, $label = null, $placeholder = '', $value = null, $success = null, $error = null)
    {
        $this->name = $name;
        $this->label = $label ?? ucfirst($name);
        $this->placeholder = $placeholder;
        
        if ($value !== null) {
            // First decode HTML entities
            $decodedValue = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Convert the incorrect character encoding to proper UTF-8
            $this->value = str_replace(
                ['ǎ', 'ǐ', 'ǔ', 'ǒ'], 
                ['ă', 'î', 'ț', 'ș'], 
                $decodedValue
            );
        } else {
            $this->value = null;
        }
        
        $this->success = $success;
        $this->error = $error;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.input');
    }
}
