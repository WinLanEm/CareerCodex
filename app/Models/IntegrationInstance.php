<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntegrationInstance extends Model
{
    protected $fillable = [
        'integration_id',
        'external_id',
        'site_url',
    ];
}
