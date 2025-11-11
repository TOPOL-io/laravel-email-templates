<?php

declare(strict_types=1);

namespace Topol\EmailTemplates;

use Illuminate\Support\ServiceProvider;

class EmailTemplatesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('email-templates.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'email-templates');

        // Register the API client
        $this->app->singleton(ApiClient::class, function ($app) {
            return new ApiClient();
        });

        // Register the main class to use with the facade
        $this->app->singleton('email-templates', function ($app) {
            /** @var \Illuminate\Contracts\Foundation\Application $app */
            /** @var ApiClient $apiClient */
            $apiClient = $app->make(ApiClient::class);
            return new EmailTemplates($apiClient);
        });
    }

}
