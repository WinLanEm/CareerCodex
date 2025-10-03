<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeveloperActivity extends Model
{
    protected $fillable = [
        'integration_id',
        'external_id',
        'type',
        'title',
        'repository_name',
        'is_approved',
        'url',
        'completed_at',
        'additions',
        'deletions',
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
