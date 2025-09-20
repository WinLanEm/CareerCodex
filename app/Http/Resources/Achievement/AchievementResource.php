<?php

namespace App\Http\Resources\Achievement;

use App\Http\Resources\BaseJsonResource;
use Illuminate\Http\Request;

class AchievementResource extends BaseJsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'result' => $this->result,
            'hours_spent' => $this->hours_spent,
            'date' => $this->date,
            'skills' => $this->skills
        ];
    }
}
