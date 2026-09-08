<?php

namespace IProfile\Seo\Tests;

use IProfile\Seo\SeoServiceProvider;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        // مايجريشن الحزمة ملفات .php.stub (تُنشر باسم مؤرّخ عند التثبيت)،
        // و loadMigrationsFrom لا يلتقط هذا الامتداد — تُشغَّل يدوياً.
        foreach (glob(__DIR__.'/../database/migrations/*.php.stub') ?: [] as $stub) {
            (include $stub)->up();
        }

        // جداول التجهيز تُبنى صراحةً: أوضح وأسرع من اكتشاف المسارات.
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title')->nullable();
            $table->text('excerpt')->nullable();
            $table->text('body')->nullable();
            $table->string('cover_image')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
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
