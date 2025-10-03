<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('app:sync-services-data-command')->everyFourHours();
Schedule::command('app:sync-services-instances-data-command')->daily();
