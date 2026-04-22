<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //For POCOR-8215 start...
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
        //For POCOR-8215 end...

        //POCOR-9509: Apply timezone from config_items so date logic matches what the admin configured in the UI
        $this->applySystemTimezone();
    }

    private function applySystemTimezone(): void
    {
        try {
            $row = DB::table('config_items')
                ->where('code', 'time_zone')
                ->value('value');

            if ($row && @timezone_open($row) !== false) {
                config(['app.timezone' => $row]);
                date_default_timezone_set($row);
                Carbon::setTimezone($row);
            }
        } catch (\Throwable $e) {
            // DB not ready (e.g. during migrations) — stay on default timezone
        }
    }
}
