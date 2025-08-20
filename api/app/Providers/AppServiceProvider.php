<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;

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
        DB::whenQueryingForLongerThan(200, function ($conn, $event) {
            Log::warning('Slow query', [
                'sql'      => $event->sql,
                'time_ms'  => $event->time,
                'bindings' => $event->bindings,
            ]);
        });
        DB::disableQueryLog();
        //For POCOR-9352 end...
    }
}
