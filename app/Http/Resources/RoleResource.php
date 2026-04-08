<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->resource;

        $selfLink = url('/api/roles/'.$role->id);

        return [
            'data' => [
                'type' => 'roles',
                'role_id' => $role->id,
                'attributes' => [
                    'name' => $role->name,
                    'slug' => $role->slug,
                ],
            ],
            'links' => [
                'self' => $selfLink,
            ],
        ];
    }
}
