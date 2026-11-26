@props([
    'icon' => 'bi-inbox',
    'title' => 'No records found',
    'message' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-5 px-3']) }}>
    <i class="bi {{ $icon }} fs-2 text-secondary d-block mb-2" aria-hidden="true"></i>
    <h3 class="h6 text-navy mb-1">{{ $title }}</h3>
    @if ($message)
        <p class="small text-secondary mb-3">{{ $message }}</p>
    @endif
    @if (trim($slot) !== '')
        <div>{{ $slot }}</div>
    @endif
</div>
