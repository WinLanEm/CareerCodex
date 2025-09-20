<?php

namespace App\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BaseJsonResource extends JsonResource
{
    protected bool $status;
    protected int $statusCode;

    public function __construct($resource, bool $status = true, int $statusCode = 200)
    {
        parent::__construct($resource);
        $this->status = $status;
        $this->statusCode = $statusCode;
    }

    public function toResponse($request = null): JsonResponse
    {
        $request = $request ?: request();
        $data = array_merge($this->toArray($request), ['status' => $this->status]);
        return response()->json($data, $this->statusCode);
    }
}
