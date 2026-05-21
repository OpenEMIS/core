<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
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
            $tz = Cache::remember('system_timezone', 600, function () { //POCOR-9509: cache 10 min — avoid per-request DB hit
                $row = DB::table('config_items')->where('code', 'time_zone')->value('value');
                return ($row && @timezone_open($row) !== false) ? $row : 'UTC'; //POCOR-9509: invalid/missing → Greenwich (UTC)
            });
            config(['app.timezone' => $tz]);
            date_default_timezone_set($tz); //POCOR-9509: also seeds Carbon — Carbon honours PHP default tz.
            //POCOR-9719: align MySQL session TZ with PHP TZ so DATETIME columns
            //and CURRENT_TIMESTAMP defaults round-trip in the same wallclock.
            //Without this, alert_queue.available_at drifts by the UTC offset
            //(Tonga saw +13h, scheduling alerts for "tomorrow 03:00" instead of "today 14:00").
            //POCOR-9719: removed the broken Carbon::setTimezone($tz) — that's an
            //instance method called statically; it was throwing silently and
            //preventing every subsequent line from running.
            DB::statement('SET time_zone = ?', [$tz]);
        } catch (\Throwable $e) {
            // DB not ready (e.g. during migrations) — stay on default timezone
        }
    }
}
