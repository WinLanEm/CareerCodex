<?php

namespace App\Http\Resources\AllActivities;

use App\Http\Resources\Achievement\AchievementResource;
use App\Http\Resources\BaseJsonResource;
use App\Http\Resources\DeveloperActivity\DeveloperActivityResource;
use App\Models\Achievement;
use App\Models\DeveloperActivity;

class AllActivitiesResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        if ($this->resource instanceof DeveloperActivity) {
            return [
                'developer_activity' => new DeveloperActivityResource($this->resource)
            ];
        }

        if ($this->resource instanceof Achievement) {
            return [
                'achievement' => new AchievementResource($this->resource)
            ];
        }
        return [];
    }

}
