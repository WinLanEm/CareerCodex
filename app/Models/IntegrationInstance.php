<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationInstance extends Model
{
    protected $fillable = [
        'has_websocket',
        'integration_id',
        'external_id',
        'site_url',
    ];
}
