<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Textarea extends Component
{
    private $name;
    private $title;
    private $placeholder;
    private $item;
    /**
     * @var int
     */
    public $size;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($name, $title,$size=12, $item = null)
    {
        $this->name = $name;
        $this->title = $title;
        $this->item = isset($item) ? $item->getTranslations($name) : null;
        $this->size = $size;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.textarea', ['name' => $this->name, 'title' => $this->title, 'item' => $this->item]);
    }
}
