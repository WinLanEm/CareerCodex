<?php

namespace App\Http\Resources\User;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class AuthResource extends BaseJsonResource
{
    private string $token;

    private string $message;

    public function __construct($resource, string $message, string $token)
    {
        parent::__construct($resource);
        $this->message = $message;
        $this->token = $token;
    }

    public function toArray(Request $request): array
    {
        return [
            'message' => $this->message,
            'user' => new UserResource($this->resource),
            'token' => $this->token,
        ];
    }
}
