<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Webhook extends Model
{
    protected $fillable = [
        'integration_id',
        'secret',
        'repository_id',
        'events',
        'active',
        'repository',
        'webhook_id',
    ];

    protected $casts = [
        'events' => 'array',
        'secret' => 'encrypted'
    ];

    public function integration():BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }
}
