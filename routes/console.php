<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('blf:run-matching')->dailyAt('02:00');
Schedule::command('blf:flag-disposal-eligible')->dailyAt('03:00');
Schedule::command('blf:backup-db')->dailyAt('04:00');
