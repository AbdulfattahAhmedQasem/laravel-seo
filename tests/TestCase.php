<?php

namespace IProfile\Seo\Tests;

use IProfile\Seo\SeoServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [SeoServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.env', 'production'); // حتى لا يُفرض noindex
        $app['config']->set('database.default', 'testing');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
