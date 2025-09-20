<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class MessageResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        if (is_string($this->resource)) {
            return ['message' => $this->resource];
        }

        return parent::toArray($request);
    }
}

