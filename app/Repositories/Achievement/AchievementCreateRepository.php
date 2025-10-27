<?php

namespace App\Repositories\Achievement;

use App\Contracts\Repositories\Achievement\AchievementCreateRepositoryInterface;
use App\Models\Achievement;

class AchievementCreateRepository implements AchievementCreateRepositoryInterface
{
    public function create(array $data,int $userId): ?Achievement
    {
        return Achievement::create([
            'user_id' => $userId,
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
