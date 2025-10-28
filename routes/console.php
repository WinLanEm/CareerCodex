<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('app:sync-services-data-command')->everyFourHours();
Schedule::command('app:sync-services-instances-data-command')->daily();
