<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Map extends Component
{
    public $title;
    public $size;
    public $lat;
    public $lng;
    public $mapId;

    public function __construct(
        $title = null,
        $size = 12,
        $lat = null,
        $lng = null
    ) {
        $this->title = $title;
        $this->size  = $size;
        $this->lat   = $lat;
        $this->lng   = $lng;
        $this->mapId = 'map_' . uniqid(); 
    }

    public function render()
    {
        return view('components.map');
    }
}
