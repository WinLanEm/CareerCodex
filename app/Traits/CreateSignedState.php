<?php

namespace App\Traits;

trait CreateSignedState
{
    protected function createSignedState(array $data): string
    {
        $state = base64_encode(json_encode($data));
        $signature = hash_hmac('sha256', $state, config('app.key'));
        return $state . '.' . $signature;
    }
}
