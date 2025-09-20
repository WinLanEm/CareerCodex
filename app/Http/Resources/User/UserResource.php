<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class UserResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'provider' => $this->provider,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at->toDateTimeString(),
        ];
    }
}
