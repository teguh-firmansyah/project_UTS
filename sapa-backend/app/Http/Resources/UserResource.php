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
            'room' => $this->room,   // BARU
            'bio' => $this->bio,     // BARU
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null, // full URL
            'is_active' => $this->is_active,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
        ];
    }
}
