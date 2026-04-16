<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Check subscription expiry and send reminder emails daily at 08:00 WIB (01:00 UTC)
Schedule::command('theday:check-subscription-expiry')->dailyAt('01:00');

