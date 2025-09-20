<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
class UrlResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        if (is_string($this->resource)) {
            return ['url' => $this->resource];
        }

        return parent::toArray($request);
    }
}
