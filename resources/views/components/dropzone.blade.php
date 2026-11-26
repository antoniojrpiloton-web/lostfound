@props([
    'id',
    'name',
    'label' => null,
    'hint' => null,
    'accept' => 'image/jpeg,image/png,image/webp',
    'multiple' => false,
    'required' => false,
    'compact' => false,
    'maxFiles' => null,
    'validateQuality' => null,
])

@php
    $baseKey = str_replace('[]', '', $name);
    $hasError = $errors->has($baseKey) || $errors->has($baseKey.'.*');
    $shouldValidate = $validateQuality ?? (
        str_contains((string) $accept, 'image/')
        && ! str_contains((string) $accept, 'pdf')
    );
@endphp

<div
    class="blf-dropzone-wrapper"
    data-dropzone-root
    @if($maxFiles) data-max-files="{{ $maxFiles }}" @endif
    @if($shouldValidate) data-validate-url="{{ route('api.images.precheck') }}" @endif
>
    @if ($label)
        <label for="{{ $id }}" class="form-label small fw-medium">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <div class="blf-dropzone {{ $compact ? 'is-compact' : '' }} {{ $hasError ? 'border-danger' : '' }}" data-dropzone-area>
        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="file"
            accept="{{ $accept }}"
            class="blf-dropzone-input"
            data-dropzone-input
            @if ($multiple) multiple @endif
            @if ($required) required @endif
        >
        <div class="blf-dropzone-icon" aria-hidden="true">
            <i class="bi bi-cloud-arrow-up"></i>
        </div>
        <div class="blf-dropzone-title">
            {{ __('Drag & drop files here, or') }} <span class="blf-dropzone-browse">{{ __('browse') }}</span>
        </div>
        @if ($hint)
            <div class="blf-dropzone-hint">{{ $hint }}</div>
        @endif
    </div>

    <div class="blf-dropzone-previews" data-dropzone-previews></div>

    @error($baseKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
    @error($baseKey.'.*')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
