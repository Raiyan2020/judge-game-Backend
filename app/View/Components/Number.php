<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Number extends Component
{
    public $title;
    public $name;
    public $options;
    public $size;
    public $step;
    public $value;
    public $extraClass;


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($title,$name,$options=[],$size=null,$step='any',$value=null ,$extraClass=null )
    {
        
        $this->title = $title;
        $this->name = $name;
        $this->options = $options;
        $this->size = $size;
        $this->step = $step;
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
        return view('components.number');
    }
}
