@extends('layouts.admin')

@section('eyebrow', __('Maintenance & Security'))
@section('title', __('System Health & Backups'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Workspace'), 'url' => route('admin.dashboard')], ['label' => __('System Health & Backups')]]" />
@endsection

@section('actions')
    <form method="POST" action="{{ route('admin.system_health.backup') }}" class="m-0">
        @csrf
        <button type="submit" class="btn btn-sm btn-primary px-3">
            <i class="bi bi-database-down me-1" aria-hidden="true"></i>{{ __('Run Database Backup Now') }}
        </button>
    </form>
@endsection

@section('content')
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="blf-panel-card p-3 h-100">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="small fw-semibold text-secondary">{{ __('AI Vision Service (OpenCV + OpenCLIP)') }}</span>
                    @if ($validatorStatus['online'])
                        <span class="badge bg-success-subtle text-success-emphasis border border-success-subtle">{{ __('Online') }}</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">{{ __('Offline / Fallback') }}</span>
                    @endif
                </div>
                <div class="fw-bold text-navy mb-1">
                    {{ $validatorStatus['clip_enabled'] ? __('OpenCLIP ViT-B-32 Active') : __('Local PHP GD Fallback') }}
                </div>
                <div class="small text-secondary">
                    {{ __('Endpoint:') }} <code>{{ $validatorStatus['url'] }}</code>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="blf-panel-card p-3 h-100">
                <div class="small fw-semibold text-secondary mb-2">{{ __('Visual Search Index Coverage') }}</div>
                <div class="h4 fw-bold text-navy mb-1" style="font-variant-numeric: tabular-nums;">
                    {{ number_format($stats['embeddings']) }} / {{ number_format($stats['images']) }}
                </div>
                <div class="small text-secondary">
                    @foreach ($stats['embedding_models'] as $model => $count)
                        <span class="badge bg-light text-navy border me-1">{{ $model }}: {{ number_format($count) }}</span>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="blf-panel-card p-3 h-100">
                <div class="small fw-semibold text-secondary mb-2">{{ __('Database Overview') }}</div>
                <div class="h4 fw-bold text-navy mb-1" style="font-variant-numeric: tabular-nums;">
                    {{ number_format($stats['reports']) }} {{ __('Reports') }}
                </div>
                <div class="small text-secondary">
                    {{ number_format($stats['matches']) }} {{ __('candidate matches') }} · {{ count($backups) }} {{ __('SQL backup(s)') }}
                </div>
            </div>
        </div>
    </div>

    <div class="blf-panel-card overflow-hidden">
        <div class="p-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h2 class="h6 fw-bold text-navy mb-0">{{ __('Database Backup Snapshots') }}</h2>
                <div class="small text-secondary">{{ __('Stored privately in storage/app/private/backups (automated daily at 04:00 via blf:backup-db).') }}</div>
            </div>
        </div>

        @if ($backups === [])
            <div class="p-4 text-center small text-secondary">
                {{ __('No database backups found yet. Click "Run Database Backup Now" to create a snapshot.') }}
            </div>
        @else
            <div class="table-responsive">
                <table class="table blf-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Action') }}</th>
                            <th>{{ __('Snapshot File') }}</th>
                            <th>{{ __('File Size') }}</th>
                            <th>{{ __('Created At') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($backups as $backup)
                            <tr>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.system_health.backup.download', $backup['name']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1" aria-hidden="true"></i>{{ __('Download SQL') }}
                                    </a>
                                </td>
                                <td><code class="text-navy">{{ $backup['name'] }}</code></td>
                                <td class="small text-secondary">{{ number_format($backup['size'] / 1048576, 2) }} MB</td>
                                <td class="small text-secondary">{{ $backup['modified_at']->format('M d, Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endsection
