<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Integration extends Model
{
    protected $fillable = [
        'user_id',
        'service',
        'service_user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'provider_instance_id',
        'next_check_provider_instances_at',
        'site_url'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'next_check_provider_instances_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function providerInstance(): BelongsToMany
    {
        return $this->belongsToMany(IntegrationInstance::class);
    }

    public function webhook():HasOne
    {
        return $this->hasOne(Webhook::class);
    }
}
