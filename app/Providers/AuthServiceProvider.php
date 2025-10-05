<?php

namespace App\Providers;

use App\Models\Achievement;
use App\Models\DeveloperActivity;
use App\Models\Workspace;
use App\Policies\AchievementPolicy;
use App\Policies\DeveloperActivityPolicy;
use App\Policies\WorkspacePolicy;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Workspace::class => WorkspacePolicy::class,
        Achievement::class => AchievementPolicy::class,
        DeveloperActivity::class => DeveloperActivityPolicy::class,
    ];
}
