<?php

namespace App\Http\Resources\DeveloperActivity;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class DeveloperActivityResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'integration_id' => $this->integration->id,
            'title' => $this->title,
            'repository_name' => $this->repository_name,
            'type' => $this->type,
            'service' => $this->integration->service,
            'url' => $this->url,
            'completed_at' => $this->completed_at,
            'additions' => $this->additions ?? 0,
            'deletions' => $this->deletions ?? 0,
            'is_approved' => $this->is_approved,
        ];
    }
}
