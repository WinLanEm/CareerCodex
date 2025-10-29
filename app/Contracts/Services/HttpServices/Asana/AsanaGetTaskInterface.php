<?php

namespace App\Contracts\Services\HttpServices\Asana;

use App\Models\Integration;
use Illuminate\Http\Client\Response;

interface AsanaGetTaskInterface
{
    public function getTask(Integration $integration, string $taskGid): Response;
}
