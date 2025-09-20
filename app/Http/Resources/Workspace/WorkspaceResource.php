<?php

namespace App\Http\Resources\Workspace;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class WorkspaceResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date
        ];
    }
}
