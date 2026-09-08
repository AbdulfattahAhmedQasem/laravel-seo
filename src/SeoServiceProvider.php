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
            ->hasMigrations([
                'create_seo_meta_table',
                'create_seo_redirects_table',
            ])
            ->hasRoute('web')
            ->hasCommands([
                InstallCommand::class,
                ClearSeoCacheCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        /*
         | scoped لا singleton: تحت Octane يبقى الـ singleton حياً بين
         | الطلبات، فتتسرّب بيانات SEO لصفحة إلى صفحة زائر آخر.
         | scoped يُعاد ضبطه مع كل طلب.
         */
        $this->app->scoped(SeoManager::class, fn () => new SeoManager);
    }

    public function packageBooted(): void
    {
        /*
         | يتيح <x-seo::meta /> و <x-seo::schema.organization />.
         | المكوّنات مجهولة (ملفات Blade بلا أصناف)، لذا
         | anonymousComponentNamespace لا componentNamespace.
         */
        Blade::anonymousComponentNamespace('seo::components', 'seo');
    }
}
