<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'identity_number' => $this->identity_number,
            'class_name' => $this->class_name,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'is_active' => $this->is_active,
            'roles' => $this->getRoleNames(), // dari Spatie — array nama role
            'permissions' => $this->getAllPermissions()->pluck('name'), // untuk frontend guard
        ];
    }
}
