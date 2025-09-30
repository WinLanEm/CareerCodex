<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationInstance extends Model
{
    protected $fillable = [
        'has_websocket',
        'integration_id',
        'external_id',
        'site_url',
        'repository_name',
        'default_branch',
        'meta'
    ];

    protected $casts = [
        'meta' => 'array'
    ];

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
