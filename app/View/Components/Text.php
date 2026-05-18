<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Text extends Component
{
    public $title;
    public $name;
    public $options;
    public $size;
    public $type;
    public $extraClass;
    public $value;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($title, $name, $type = 'text',$extraClass=null, $options = [], $size = null,$value=null)
    {
        //
        $this->title = $title;
        $this->name = $name;
        $this->options = $options;
        $this->size = $size;
        $this->type = $type;
        $this->value = $value;
        $this->extraClass = $extraClass;

    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.text');
    }
}
