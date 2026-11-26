@props([
    'label',
    'value',
    'subtitle' => null,
    'badge' => null,
    'icon' => 'bi-bar-chart',
    'tone' => 'ocean',
])

@php
    $tones = [
        'ocean' => ['bg' => 'rgba(8, 126, 164, 0.12)', 'color' => '#087EA4'],
        'teal' => ['bg' => 'rgba(13, 148, 136, 0.12)', 'color' => '#0D9488'],
        'navy' => ['bg' => 'rgba(18, 48, 71, 0.10)', 'color' => '#123047'],
        'warning' => ['bg' => 'rgba(217, 119, 6, 0.12)', 'color' => '#B45309'],
        'danger' => ['bg' => 'rgba(220, 38, 38, 0.12)', 'color' => '#B91C1C'],
    ];
    $style = $tones[$tone] ?? $tones['ocean'];
@endphp

<div {{ $attributes->merge(['class' => 'blf-kpi-card tone-'.$tone]) }}>
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <div class="d-flex align-items-center gap-2 min-w-0">
            <span class="blf-kpi-icon" style="background-color: {{ $style['bg'] }}; color: {{ $style['color'] }};" aria-hidden="true">
                <i class="bi {{ $icon }}"></i>
            </span>
            <span class="blf-kpi-label text-truncate">{{ $label }}</span>
        </div>
        @if ($badge)
            <span class="blf-kpi-pill text-nowrap" style="background-color: {{ $style['bg'] }}; color: {{ $style['color'] }};">
                {{ $badge }}
            </span>
        @endif
    </div>

    <div class="blf-kpi-value mb-2">{{ $value }}</div>

    @if ($subtitle)
        <div class="small text-secondary">{{ $subtitle }}</div>
    @endif
</div>
