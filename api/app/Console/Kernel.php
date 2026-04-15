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
        //POCOR-9509: schedule alert discovery separately so scheduled rules are queued without Dashboard traffic
        $schedule->command('alerts:check-and-queue')
            ->everyMinute()
            ->withoutOverlapping();

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

        // POCOR-9509: Alert queue processing — throttle via ALERTS_PROCESS_LIMIT in .env (default 50/run)
        $schedule->command('alerts:process', ['--limit=' . config('alerts.process_limit', 20)])
            ->everyMinute()
            ->withoutOverlapping()
            ->onFailure(function () {
                \Illuminate\Support\Facades\Log::error('[AlertScheduler] Alert queue processor failed');
            });
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
