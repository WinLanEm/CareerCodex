<?php

namespace App\Http\Resources\Integration;

use App\Http\Resources\BaseJsonResource;

class IndexIntegrationResource extends BaseJsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'service' => $this->service
        ];
    }
}
