<?php

namespace App\Http\Resources\Achievement;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class IndexAchievementResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'achievement' => AchievementResource::collection($this->resource->items()),
            'paginator' => [
                'total' => $this->resource->total(),
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'last_page' => $this->resource->lastPage(),
            ]
        ];
    }
}
