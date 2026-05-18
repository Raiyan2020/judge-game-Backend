<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Translatable extends Component
{

    private $name;
    private $title;
    private $placeholder;
    private $item;
    private $size;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($name,$title,$item=null,$size=6)
    {
        //
        $this->name = $name;
        $this->title = $title;
        $this->size = $size;
        $this->item =isset($item)?$item->getTranslations($name):null ;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|string
     */
    public function render()
    {
        return view('components.translatable',['name'=>$this->name,'title'=>$this->title,'item'=>$this->item,'size'=>$this->size]);
    }
}
