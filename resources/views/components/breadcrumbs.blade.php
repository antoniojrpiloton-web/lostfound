@props([
    'items' => [],
])

@if (count($items) > 0)
    <nav aria-label="{{ __('Breadcrumb') }}">
        <ol {{ $attributes->merge(['class' => 'breadcrumb mb-0 small']) }}>
            @foreach ($items as $item)
                @if ($loop->last || empty($item['url']))
                    <li class="breadcrumb-item active text-navy fw-medium" aria-current="page">{{ $item['label'] }}</li>
                @else
                    <li class="breadcrumb-item">
                        <a href="{{ $item['url'] }}" class="text-decoration-none text-secondary">{{ $item['label'] }}</a>
                    </li>
                @endif
            @endforeach
        </ol>
    </nav>
@endif
