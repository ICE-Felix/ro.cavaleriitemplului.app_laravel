<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Textarea extends Component
{
    public $name;
    public $label;
    public $value;
    public $rows;
    public $error;
    public $success;
    public $type; // Added type attribute

    public function __construct($name, $label = null, $value = null, $rows = 10, $error = null, $success = null, $type = 'text')
    {
        $this->name = $name;
        $this->label = $label ?? ucfirst($name);
        $this->value = $value;
        $this->rows = $rows;
        $this->error = $error;
        $this->success = $success;
        $this->type = $type; // Initialize type in the constructor
    }

    public function render(): \Illuminate\Contracts\View\Factory|\Illuminate\Foundation\Application|\Illuminate\Contracts\View\View|\Illuminate\Contracts\Support\Htmlable|string|\Closure|\Illuminate\Contracts\Foundation\Application
    {
        // Conditional handling for different types
        if ($this->type === 'json' && $this->value !== null) {
            // Assuming $this->value is a JSON string that might contain HTML entities
            $decodedHtml = html_entity_decode($this->value);
            // Decode the JSON to an array or object
            $decodedJson = json_decode($decodedHtml, true);

            // Check if decoding was successful or handle errors
            if (json_last_error() === JSON_ERROR_NONE) {
                // Optionally, re-encode to formatted JSON string
                $this->value = json_encode($decodedJson, JSON_PRETTY_PRINT);
            } else {
                // Handle JSON errors, perhaps logging them or setting an error message
                $this->error = 'Invalid JSON data provided.';
            }
        }

        return view('components.textarea', ['type' => $this->type]);
    }


}
