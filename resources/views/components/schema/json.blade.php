{{-- نقطة الطباعة الوحيدة لكل بيانات JSON-LD في الحزمة.
     أي مكوّن Schema يمرّ من هنا فيرث الترميز الآمن تلقائياً. --}}
@props(['data' => []])
@php $encoded = \IProfile\Seo\Support\Json::forScript($data); @endphp
@if($encoded !== '{}')
<script type="application/ld+json">{!! $encoded !!}</script>
@endif
