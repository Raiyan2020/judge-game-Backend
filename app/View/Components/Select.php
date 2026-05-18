<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Select extends Component
{
    public $title;
    public $name;
    public $options;
    public $size;
    public $items;
    public $multiple;
    public $selected;
    public $extraClass;



    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($title,$name,$options=[],$items=[],$size=null,$multiple=false,$selected=[],$extraClass =null)
    {
        //
        $this->title = $title;
        $this->name = $name;
        $this->options = $options;
        $this->size = $size;
        $this->items = $items;
        $this->multiple = $multiple;
        $this->selected = $selected;
        $this->extraClass = $extraClass;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.select');
    }
}
