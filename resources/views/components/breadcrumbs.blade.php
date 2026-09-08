{{-- مسار تنقل مرئي — جوجل يفضّل تطابق ما يراه المستخدم مع JSON-LD --}}
@props(['items' => null])
@php $items = $items ?? \IProfile\Seo\Facades\Seo::data()->breadcrumbs; @endphp
@if($items)
<nav aria-label="{{ __('مسار التنقل') }}">
    <ol {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-2 text-sm']) }}>
        @foreach($items as $i => $item)
            <li>
                @if($loop->last)
                    <span aria-current="page">{{ $item['name'] }}</span>
                @else
                    <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                    <span aria-hidden="true">/</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif
