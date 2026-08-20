<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
   
       public function toArray($request)
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'key'  => $this->key,
            'has_permission' => $this->has_permission ?? false,
            // True (individual editor only) when the member already holds this
            // via their role: the app shows it ON + locked, so a role grant is
            // no longer invisible on the member screen.
            'inherited_from_role' => $this->inherited_from_role ?? false,
        ];
    
    }
}
