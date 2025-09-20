<?php

namespace App\Http\Resources\Workspace;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class WorkspaceWrapperResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'workspace' => new WorkspaceResource($this->resource)
        ];
    }
}
