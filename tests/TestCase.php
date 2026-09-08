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
        // app()->environment() يقرأ من الحاوية لا من config، فيجب ضبط الاثنين.
        $app['config']->set('app.env', 'production');
        $app->detectEnvironment(fn () => 'production');

        $app['config']->set('app.name', 'موقع الاختبار');
        $app['config']->set('app.url', 'https://example.test');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('cache.default', 'array');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/Fixtures/migrations');
    }

    /**
     * يجعل التطبيق يتصرف كبيئة تطوير لاختبار فرض noindex.
     */
    protected function asLocalEnvironment(): void
    {
        $this->app['config']->set('app.env', 'local');
        $this->app->detectEnvironment(fn () => 'local');
    }
}
