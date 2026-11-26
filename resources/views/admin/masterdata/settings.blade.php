@extends('layouts.admin')

@section('eyebrow', __('Configuration'))
@section('title', __('System Settings'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('System Settings')]]" />
@endsection

@section('content')
    @foreach ($groupedSettings as $group => $settings)
        <div class="blf-panel-card mb-3 overflow-hidden">
            <div class="px-3 py-2 border-bottom bg-light">
                <span class="blf-page-eyebrow">{{ str_replace('_', ' ', $group) }}</span>
            </div>
            <div class="table-responsive">
                <table class="table blf-table mb-0">
                    <tbody>
                        @foreach ($settings as $setting)
                            @php
                                $isScalar = is_array($setting->value) && array_key_exists('value', $setting->value) && count($setting->value) === 1;
                                $isBool = $isScalar && is_bool($setting->value['value']);
                                $boolVal = $isBool ? (bool) $setting->value['value'] : false;
                                $displayVal = $isScalar
                                    ? ($isBool ? ($boolVal ? 'true' : 'false') : (string) $setting->value['value'])
                                    : json_encode($setting->value);
                            @endphp
                            <tr>
                                <td class="fw-medium text-navy" style="width: 38%;"><code>{{ $setting->key }}</code></td>
                                <td>
                                    @if ($isBool)
                                        <form method="POST" action="{{ route('admin.settings.update', $setting) }}" class="d-flex align-items-center justify-content-between gap-2 m-0">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="value" value="0">
                                            <div class="form-check form-switch mb-0">
                                                <input
                                                    type="checkbox"
                                                    role="switch"
                                                    id="setting_{{ $setting->id }}"
                                                    name="value"
                                                    value="1"
                                                    class="form-check-input"
                                                    aria-label="{{ $setting->key }}"
                                                    @checked($boolVal)
                                                    onchange="this.form.submit()"
                                                >
                                                <label class="form-check-label small fw-medium {{ $boolVal ? 'text-primary' : 'text-secondary' }}" for="setting_{{ $setting->id }}">
                                                    {{ $boolVal ? __('Enabled') : __('Disabled') }}
                                                </label>
                                            </div>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.settings.update', $setting) }}" class="d-flex align-items-center gap-2 m-0">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="value" value="{{ $displayVal }}" class="form-control form-control-sm" aria-label="{{ $setting->key }}">
                                            <button type="submit" class="btn btn-sm btn-outline-primary flex-shrink-0">{{ __('Save') }}</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
@endsection
