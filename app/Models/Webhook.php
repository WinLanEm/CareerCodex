<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Webhook extends Model
{
    protected $fillable = [
        'integration_id',
        'secret',
        'events',
        'active',
        'repository',
        'webhook_id',
    ];

    protected $casts = [
        'events' => 'array',
        'secret' => 'encrypted'
    ];
}
