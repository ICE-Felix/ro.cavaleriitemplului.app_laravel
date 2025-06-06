<?php

namespace App\View\Components;

use Illuminate\View\Component;

class FileBrowser extends Component
{
    public $name;
    public $isImage;
    public $value;
    public $error;
    public $success;
    public $label;
    public $preview;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($name, $label = null, $isImage = false, $value = null, $error = null, $success = null, $preview = null)
    {
        $this->name = $name;
        $this->label = $label;
        $this->isImage = $isImage;
        $this->value = $value;
        $this->error = $error;
        $this->success = $success;
        $this->preview = $preview;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.file-browser');
    }
}
