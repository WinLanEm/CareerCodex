<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeveloperActivity extends Model
{
    protected $fillable = [
        'integration_id',
        'external_id',
        'type',
        'title',
        'repository_name',
        'url',
        'completed_at',
        'additions',
        'deletions',
    ];
}
