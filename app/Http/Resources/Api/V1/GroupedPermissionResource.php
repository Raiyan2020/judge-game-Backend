<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupedPermissionResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'group' => $this['group'],

            'permissions' => PermissionResource::collection(
                $this['permissions']
            ),
        ];
    }
}
