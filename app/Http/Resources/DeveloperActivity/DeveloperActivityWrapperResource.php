<?php

namespace App\Http\Resources\DeveloperActivity;

use App\Http\Resources\BaseJsonResource;

class DeveloperActivityWrapperResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'achievement' => new DeveloperActivityResource($this->resource)
        ];
    }
}
