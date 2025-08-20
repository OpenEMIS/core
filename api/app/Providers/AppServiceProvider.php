<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


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
        // AppServiceProvider::boot()
        //For POCOR-9352 start..
        DB::enableQueryLog();

        DB::statement('SET SESSION max_execution_time=600'); // 60s

        DB::listen(function ($query) {
            if ($query->time > 300) { // миллисекунды
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                ]);
            }
        });
        DB::listen(function ($query) {
            if ($query->time > 300) { // миллисекунды
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time_ms' => $query->time,
                ]);
            }
        });
        //For POCOR-9352 end...
    }
}
