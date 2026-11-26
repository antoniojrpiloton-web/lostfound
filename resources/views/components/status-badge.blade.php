@props([
    'status',
    'label' => null,
    'dot' => true,
])

@php
    $value = $status instanceof \BackedEnum ? $status->value : strtolower((string) $status);

    $palette = match ($value) {
        'verified', 'active', 'passed', 'approved', 'completed', 'returned', 'resolved', 'sent', 'overridden' => 'blf-pill-success',
        'pending', 'under_review', 'info_requested', 'review', 'claim_pending', 'awaiting_turnover', 'proposed', 'eligible', 'suggested', 'queued' => 'blf-pill-warning',
        'rejected', 'suspended', 'revoked', 'cancelled', 'failed', 'disposed' => 'blf-pill-danger',
        'found', 'in_custody', 'possible' => 'blf-pill-info',
        'lost', 'open', 'submitted' => 'blf-pill-primary',
        default => 'blf-pill-neutral',
    };

    $text = $label ?? ucwords(str_replace('_', ' ', $value));
@endphp

<span {{ $attributes->merge(['class' => 'blf-status-pill '.$palette]) }}>
    @if ($dot)
        <span class="blf-status-dot" aria-hidden="true"></span>
    @endif
    <span>{{ $text }}</span>
</span>
