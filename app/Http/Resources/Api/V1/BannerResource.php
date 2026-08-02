<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
          'id'=>$this->id,
          // `title` is a Spatie translatable; a row stored as a plain (non-JSON)
          // string can throw on read and 500 the whole /banners list. rescue()
          // degrades that one field to '' instead so the banners still load.
          'title'=>rescue(fn () => (string) $this->title, ''),
          'image'=>$this->image,
          'url'=>$this->url
          ];
    }
}
