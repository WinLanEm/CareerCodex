<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\WorkspaceAchievementUpdateOrCreateRepositoryInterface;
use App\Models\Achievement;

class WorkspaceAchievementUpdateOrCreateRepository implements WorkspaceAchievementUpdateOrCreateRepositoryInterface
{
    public function updateOrCreate(array $data, $workspaceId = null): Achievement
    {
        return Achievement::updateOrCreate(
            ['title' => $data['title'],'link' => $data['link'] ?? null,'workspace_id' => $workspaceId],
            [
                'description' => $data['description'],
                'result' => $data['result'] ?? null,
                'hours_spent' => $data['hours_spent'] ?? 0,
                'date' => $data['date'] ?? null,
                'skills' => $data['skills'] ?? null,
                'is_approved' => $data['is_approved'] ?? true,
                'is_from_provider' => $data['is_from_provider'] ?? false,
                'integration_instance_id' => $data['integration_instance_id'] ?? null,
                'project_name' => $data['project_name'] ?? null,
            ]
        );
    }
}
