<?php

namespace App\Http\Resources\DeveloperActivity;

use App\Http\Resources\BaseJsonResource;

class DeveloperActivityWrapperResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'developer_activity' => new DeveloperActivityResource($this->resource)
        ];
    }
}
