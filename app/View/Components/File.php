<?php

namespace App\View\Components;

use Illuminate\View\Component;

class File extends Component
{

    public $title;
    public $name;
    public $size;
    public $options;

    public function __construct($title, $name,$size=6,$options=[])
    {
        //

        $this->title = $title;
        $this->name = $name;
        $this->size = $size;
        $this->options=$options;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.file');
    }
}
