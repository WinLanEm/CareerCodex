<?php

namespace App\Repositories\Workspace;

use App\Contracts\Repositories\Workspace\CreateWorkspaceRepositoryInterface;
use App\Models\Workspace;

class CreateWorkspaceRepository implements CreateWorkspaceRepositoryInterface
{
    public function create(array $data): ?Workspace
    {
        return Workspace::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'type' => $data['type'],
            'description' => $data['description'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);
    }
}
