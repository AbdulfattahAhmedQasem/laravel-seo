<?php

namespace IProfile\Seo\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'seo:install {--force : استبدال الملفات الموجودة}';

    protected $description = 'تثبيت حزمة SEO: نشر الإعدادات والمايجريشن وتشغيلها';

    public function handle(): int
    {
        $this->components->info('تثبيت حزمة SEO...');

        /**
         * وسوم النشر التي يولّدها spatie/laravel-package-tools مشتقة من
         * Package::shortName() — أي Str::after('laravel-seo', 'laravel-') = 'seo'.
         * لذلك الوسوم هي seo-config و seo-migrations لا laravel-seo-*.
         *
         * كل وسم مقرون بمتحقق يؤكد وصول الملف فعلاً، فلا نبلّغ عن نجاح وهمي.
         */
        $steps = [
            'ملف الإعدادات config/seo.php' => [
                'seo-config',
                fn () => file_exists(config_path('seo.php')),
            ],
            'ملفات المايجريشن' => [
                'seo-migrations',
                fn () => $this->migrationExists('create_seo_meta_table')
                    && $this->migrationExists('create_seo_redirects_table'),
            ],
        ];

        $published = true;

        foreach ($steps as $label => [$tag, $verifier]) {
            // components->task() يُرجع void، لذا تُلتقط النتيجة داخل الإغلاق نفسه.
            $this->components->task($label, function () use ($tag, $verifier, &$published) {
                $this->callSilent('vendor:publish', [
                    '--tag' => $tag,
                    '--force' => $this->option('force'),
                ]);

                $ok = $verifier();
                $published = $published && $ok;

                return $ok;
            });
        }

        if (! $published) {
            $this->newLine();
            $this->components->error('فشل نشر بعض الملفات. تحقق من الوسوم عبر: php artisan vendor:publish --provider="IProfile\Seo\SeoServiceProvider"');

            return self::FAILURE;
        }

        if ($this->confirm('تشغيل المايجريشن الآن؟', true)) {
            $this->call('migrate');
        }

        $this->newLine();
        $this->components->info('تم التثبيت. الخطوات التالية:');
        $this->line('  1. أضف <x-seo::meta /> داخل <head> في اللاياوت');
        $this->line('  2. أضف HandleSeoRedirects عالمياً عبر $middleware->append() في bootstrap/app.php');
        $this->line('  3. أضف use HasSeo; و seoUrl() إلى موديلاتك');
        $this->line('  4. عرّف مجموعات sitemap في config/seo.php');
        $this->newLine();

        return self::SUCCESS;
    }

    protected function migrationExists(string $name): bool
    {
        return count(glob(database_path("migrations/*_{$name}.php"))) > 0;
    }
}
