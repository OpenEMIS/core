<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // POCOR-9257: Webhook queue processing
        $schedule->command('webhooks:process', ['--once'])
            ->everyMinute()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('[WebhookScheduler] Webhook queue processor failed');
            })
            ->onSuccess(function () {
                // \Illuminate\Support\Facades\Log::debug('[WebhookScheduler] Webhook queue processor completed successfully');
            });

        //POCOR-9719: alerts:check — fills alert_queue from rules; once a day is enough for scheduled-type alerts.
        $schedule->command('alerts:check')
            ->dailyAt('02:00')
            ->weekdays()
            ->withoutOverlapping(120)
            ->runInBackground();

        //POCOR-9719: alerts:send — every cron tick; delivery latency = cron cadence, no hard-coded throttle.
        $schedule->command('alerts:send', ['--limit' => env('ALERT_SEND_LIMIT', 50)])
            ->everyMinute()
            ->withoutOverlapping(60)
            ->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
