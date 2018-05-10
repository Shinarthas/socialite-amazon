<?php

namespace Revolution\Socialite\Amazon;

use Laravel\Socialite\SocialiteServiceProvider;
use Laravel\Socialite\Contracts\Factory;
use Laravel\Socialite\Facades\Socialite;

class AmazonServiceProvider extends SocialiteServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the service provider.
     *
     * @return void
     */
    public function boot()
    {
        Socialite::extend('amazon', function ($app) {
            $config = $app['config']['services.amazon'];

            return Socialite::buildProvider(AmazonProvider::class, $config);
        });
    }
}
