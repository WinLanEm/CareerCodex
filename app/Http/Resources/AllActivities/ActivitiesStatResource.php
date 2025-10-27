<?php

namespace App\Http\Resources\AllActivities;

use App\Http\Resources\BaseJsonResource;

class ActivitiesStatResource extends BaseJsonResource
{
    public function toArray($request): array
    {
        return [
            'per_month' => [
                'developer_activities' => $this->resource['per_month']['developer_activities'],
                'achievements' => $this->resource['per_month']['achievements'],
            ],
            'per_week' => [
                'developer_activities' => $this->resource['per_week']['developer_activities'],
                'achievements' => $this->resource['per_week']['achievements'],
            ],
            'per_days' => $this->resource['per_days'],
            'streak' => $this->resource['streak'],
        ];
    }
}
