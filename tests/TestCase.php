<?php

declare(strict_types=1);

namespace Topol\EmailTemplates\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Topol\EmailTemplates\EmailTemplatesServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            EmailTemplatesServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default configuration
        $app['config']->set('email-templates.api_url', 'https://app.topol.io/api');
        $app['config']->set('email-templates.api_key', 'test-api-key');
        $app['config']->set('email-templates.timeout', 30);
        $app['config']->set('email-templates.cache.enabled', true);
        $app['config']->set('email-templates.cache.ttl', 3600);
        $app['config']->set('email-templates.cache.prefix', 'topol_email_template_');

        // Use array cache driver for testing
        $app['config']->set('cache.default', 'array');
    }
}
