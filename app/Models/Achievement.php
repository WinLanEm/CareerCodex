<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    protected $fillable = [
        'workspace_id',
        'integration_instance_id',
        'title',
        'description',
        'result',
        'hours_spent',
        'date',
        'skills',
        'is_approved',
        'is_from_provider',
        'project_name',
        'link',
    ];

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    protected $casts = [
        'skills' => 'array',
    ];
}
