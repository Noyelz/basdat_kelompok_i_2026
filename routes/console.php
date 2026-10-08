<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:expire-pending-orders')->everyMinute()->withoutOverlapping();
Schedule::command('app:create-zakat-reminders')->dailyAt('08:00')->timezone('Asia/Jakarta')->withoutOverlapping();

Schedule::command('app:sync-gold-price')
    ->dailyAt(config('services.gold_price.daily_at'))
    ->timezone(config('services.gold_price.timezone'))
    ->withoutOverlapping(5)
    ->when(fn (): bool => (bool) config('services.gold_price.enabled'));
