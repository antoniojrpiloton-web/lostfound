@extends('layouts.public')

@section('title', __('Notifications'))

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="blf-page-eyebrow">{{ __('Inbox') }}</div>
            <h1 class="h4 text-navy fw-bold mb-0">{{ __('Notifications') }}</h1>
        </div>
        @if (auth()->user()->unreadNotifications()->exists())
            <form method="POST" action="{{ route('notifications.read_all') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Mark All as Read') }}</button>
            </form>
        @endif
    </div>

    <div class="blf-panel-card overflow-hidden">
        <div class="list-group list-group-flush">
            @forelse ($notifications as $notification)
                <div class="list-group-item px-3 py-3 {{ $notification->read_at === null ? 'bg-light' : '' }}">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="small">
                            <div class="fw-semibold text-navy mb-1">
                                @if ($notification->read_at === null)
                                    <span class="blf-status-dot d-inline-block me-1 text-ocean"></span>
                                @endif
                                {{ $notification->data['title'] ?? __('Notification') }}
                            </div>
                            <div class="text-secondary mb-1">{{ $notification->data['body'] ?? '' }}</div>
                            <div class="text-secondary" style="font-size: 0.72rem;">{{ $notification->created_at?->diffForHumans() }}</div>
                        </div>
                        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="m-0 flex-shrink-0">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn btn-sm btn-outline-secondary">
                                {{ ! empty($notification->data['action_url']) ? __('Open') : __('Mark Read') }}
                            </button>
                        </form>
                    </div>
                </div>
            @empty
                <x-empty-state icon="bi-bell" :title="__('No notifications yet')" :message="__('Updates on your reports, matches, claims, and recoveries will appear here.')" />
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="p-3 border-top bg-white">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
