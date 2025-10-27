<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Achievement extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
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

    public function integrationInstance(): BelongsTo
    {
        return $this->belongsTo(IntegrationInstance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'skills' => 'array',
    ];
}
