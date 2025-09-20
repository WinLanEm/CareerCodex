<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementCreateRepositoryInterface;
use App\Models\Achievement;

class WorkspaceAchievementCreateRepository implements WorkspaceAchievementCreateRepositoryInterface
{
    public function create(array $data,int $workspaceId = null): Achievement
    {
        return Achievement::create([
            'workspace_id' => $workspaceId,
            'title' => $data['title'],
            'description' => $data['description'],
            'result' => $data['result'] ?? null,
            'hours_spent' => $data['hours_spent'] ?? 0,
            'date' => $data['date'] ?? null,
            'skills' => $data['skills'] ?? null,
            'is_approved' => $data['is_approved'] ?? true,
            'is_from_provider' => $data['is_from_provider'] ?? false,
            'provider' => $data['provider'] ?? null,
            'project_name' => $data['project_name'] ?? null,
            'link' => $data['link'] ?? null,
        ]);
    }
}
