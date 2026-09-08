<?php

namespace IProfile\Seo;

use Illuminate\Support\Facades\Blade;
use IProfile\Seo\Commands\ClearSeoCacheCommand;
use IProfile\Seo\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SeoServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-seo')
            ->hasConfigFile('seo')
            ->hasViews('seo')
            ->hasMigration('create_seo_meta_table')
            ->hasMigration('create_seo_redirects_table')
            ->hasRoute('web')
            ->hasCommands([
                InstallCommand::class,
                ClearSeoCacheCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        // مدير واحد لكل الطلب — يحمل بيانات SEO للصفحة الحالية
        $this->app->scoped(SeoManager::class, fn () => new SeoManager());
    }

    public function packageBooted(): void
    {
        // يتيح استخدام <x-seo::meta /> و <x-seo::schema.organization />
        Blade::anonymousComponentNamespace('seo::components', 'seo');
    }
}
