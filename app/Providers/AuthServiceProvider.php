<?php

namespace App\Providers;

use App\Models\Achievement;
use App\Models\DeveloperActivity;
use App\Policies\AchievementPolicy;
use App\Policies\DeveloperActivityPolicy;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Achievement::class => AchievementPolicy::class,
        DeveloperActivity::class => DeveloperActivityPolicy::class,
    ];
}
