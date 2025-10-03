<?php

namespace App\Http\Resources\DeveloperActivity;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class IndexDeveloperActivitiesResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'achievement' => DeveloperActivityResource::collection($this->resource->items()),
            'paginator' => [
                'total' => $this->resource->total(),
                'current_page' => $this->resource->currentPage(),
                'per_page' => $this->resource->perPage(),
                'last_page' => $this->resource->lastPage(),
            ]
        ];
    }
}
