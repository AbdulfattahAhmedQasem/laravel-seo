<?php

namespace IProfile\Seo;

use Illuminate\Database\Eloquent\Model;
use IProfile\Seo\Data\SeoData;

/**
 * يحمل بيانات SEO للصفحة الحالية. مُسجّل كـ scoped singleton، أي أنه
 * يُعاد ضبطه بين الطلبات تلقائياً — بما في ذلك تحت Octane، حيث الحالة
 * المشتركة بين الطلبات هي مصدر الأخطاء الأول.
 *
 * @mixin SeoData
 */
class SeoManager
{
    protected SeoData $data;

    public function __construct()
    {
        $this->data = new SeoData;
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

    public function reset(): static
    {
        $this->data = new SeoData;

        return $this;
    }

    /**
     * يبني البيانات من موديل يستخدم trait المسار HasSeo.
     *
     * يعيد المدير لا SeoData: كل دوال الواجهة تعيد المدير، وشذوذ
     * هذه الدالة كان يكسر السلسلة ويربك تلميحات المحرر.
     */
    public function for(Model $model): static
    {
        if (! method_exists($model, 'seoData')) {
            throw new \InvalidArgumentException(
                $model::class.' لا يستخدم trait المسار IProfile\Seo\Concerns\HasSeo'
            );
        }

        return $this->set($model->seoData());
    }

    /**
     * تمرير أي استدعاء غير معروف إلى كائن البيانات (واجهة سلسة).
     *
     * @param  array<int,mixed>  $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        if (! method_exists($this->data, $method)) {
            throw new \BadMethodCallException(
                'الدالة '.$method.'() غير موجودة في '.static::class.' ولا في '.SeoData::class
            );
        }

        $result = $this->data->{$method}(...$arguments);

        return $result instanceof SeoData ? $this : $result;
    }
}
