<?php

namespace IProfile\Seo;

use Illuminate\Database\Eloquent\Model;
use IProfile\Seo\Data\SeoData;

/**
 * يحمل بيانات SEO للصفحة الحالية. مُسجّل كـ scoped singleton.
 *
 * @mixin \IProfile\Seo\Data\SeoData
 */
class SeoManager
{
    protected SeoData $data;

    public function __construct()
    {
        $this->data = new SeoData();
    }

    public function data(): SeoData
    {
        return $this->data;
    }

    public function set(SeoData $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * يبني البيانات من موديل يستخدم trait المسار HasSeo.
     */
    public function for(Model $model): SeoData
    {
        if (! method_exists($model, 'seoData')) {
            throw new \InvalidArgumentException(
                get_class($model).' لا يستخدم trait المسار IProfile\Seo\Concerns\HasSeo'
            );
        }

        return $this->data = $model->seoData();
    }

    /**
     * تمرير أي استدعاء غير معروف إلى كائن البيانات (واجهة سلسة).
     */
    public function __call(string $method, array $arguments): mixed
    {
        $result = $this->data->{$method}(...$arguments);

        return $result instanceof SeoData ? $this : $result;
    }
}
