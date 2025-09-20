<?php

namespace App\Http\Resources\Achievement;

use App\Http\Resources\BaseJsonResource;

class AchievementWrapperResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'achievement' => new AchievementResource($this->resource)
        ];
    }
}
