<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class UserWrapperResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => new UserResource($this->resource),
        ];
    }
}
