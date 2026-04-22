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

        //POCOR-9509: alerts:check — ALERT_CHECK_DAILY=true → daily at ALERT_CHECK_DAILY_TIME, false → hourly
        if (env('ALERT_CHECK_DAILY', true)) { //POCOR-9509
            $schedule->command('alerts:check') //POCOR-9509: renamed from alerts:check-and-queue
                ->dailyAt(env('ALERT_CHECK_DAILY_TIME', '02:00')) //POCOR-9509
                ->weekdays()
                ->withoutOverlapping(120)
                ->runInBackground();
        } else { //POCOR-9509: fallback — run every hour if daily schedule is disabled
            $schedule->command('alerts:check')
                ->hourly()
                ->withoutOverlapping(120)
                ->runInBackground();
        }

        //POCOR-9509: alerts:send — ALERT_SEND_DAILY=true → daily at ALERT_SEND_DAILY_TIME; false/missing → every 2 min for fast event-based dispatch
        if (env('ALERT_SEND_DAILY', false)) { //POCOR-9509: default false — event-based alerts (attendance, admission) need prompt sending
            $schedule->command('alerts:send', ['--limit' => env('ALERT_SEND_LIMIT', 50)]) //POCOR-9509: ALERT_SEND_LIMIT=0 sends all
                ->dailyAt(env('ALERT_SEND_DAILY_TIME', '07:00')) //POCOR-9509
                ->weekdays()
                ->withoutOverlapping(60)
                ->runInBackground();
        } else { //POCOR-9509: default — run every 2 minutes so queued alerts dispatch promptly
            $schedule->command('alerts:send', ['--limit' => env('ALERT_SEND_LIMIT', 50)]) //POCOR-9509: ALERT_SEND_LIMIT=0 sends all
                ->everyTwoMinutes()
                ->withoutOverlapping(60)
                ->runInBackground();
        }
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
