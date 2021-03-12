<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Cashier::useCurrency(config('cart.currency'), config('cart.currency_symbol'));
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('HttpClient',function($app){
            return new \GuzzleHttp\Client([
                'base_uri' => config('services.micro.api_gateway'),
                'timeout' => config('services.micro.timeout'),
                'headers' =>[
                    'Content-Type' => 'application/json'
                ]
            ]);
        });
    }
}
