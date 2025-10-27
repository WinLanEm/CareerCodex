<?php

namespace App\Http\Resources\Achievement;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class AchievementResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'result' => $this->result,
            'hours_spent' => $this->hours_spent,
            'date' => $this->date,
            'skills' => $this->skills,
            'is_approved' => $this->is_approved,
            'link' => $this->link,
            'is_from_provider' => $this->is_from_provider,
            'service' => $this?->integrationInstance?->integration?->service,
            'created_at' => $this->created_at
        ];
    }
}
