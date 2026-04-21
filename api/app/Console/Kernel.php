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

        //POCOR-9509: Alert checker — daily at 02:00, weekdays only.
        //POCOR-9509: withoutOverlapping(120) prevents stacking if a run takes longer than expected or cron is misconfigured.
        //POCOR-9509: runInBackground() so schedule:run returns immediately and does not block other scheduled jobs.
        $schedule->command('alerts:check-and-queue')
            ->dailyAt('02:00')
            ->weekdays()
            ->withoutOverlapping(120)
            ->runInBackground();

        //POCOR-9509: Alert sender — daily at 07:00, weekdays only.
        $schedule->command('alerts:process', ['--limit' => 50])
            ->dailyAt('07:00')
            ->weekdays()
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
