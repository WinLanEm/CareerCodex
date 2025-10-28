<?php

namespace App\Http\Controllers\Services;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;

abstract class AbstractServiceCallbackController extends Controller
{
    protected function validateState($receivedState, $stringService)
    {
        if (!$receivedState) {
            return ['error' => 'Missing state parameter'];
        }

        $parts = explode('.', $receivedState, 2);
        if (count($parts) !== 2) {
            return ['error' => 'Invalid state format'];
        }

        [$state, $signature] = $parts;

        $expectedSignature = hash_hmac('sha256', $state, config('app.key'));
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('OAuth state signature mismatch', [
                'service' => $stringService,
                'ip' => request()->ip()
            ]);
            return ['error' => 'Invalid state signature'];
        }

        $stateData = json_decode(base64_decode($state), true);
        if (!is_array($stateData)) {
            return ['error' => 'Invalid state parameter'];
        }

        if (!isset($stateData['timestamp'])) {
            return ['error' => 'Invalid state data'];
        }

        if ((now()->timestamp - $stateData['timestamp']) > 3600) {
            Log::warning('OAuth state expired', [
                'service' => $stringService,
                'timestamp' => $stateData['timestamp']
            ]);
            return ['error' => 'Authentication session expired'];
        }

        return ['data' => $stateData];
    }
}
