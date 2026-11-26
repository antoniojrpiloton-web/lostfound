@extends('layouts.admin')

@section('eyebrow', __('Users & Stations'))
@section('title', __('Staff Invitations'))

@section('breadcrumbs')
    <x-breadcrumbs :items="[['label' => __('Dashboard'), 'url' => route('admin.dashboard')], ['label' => __('Invitations')]]" />
@endsection

@section('actions')
    @can('create', App\Models\OrganizationInvitation::class)
        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createInvitationModal">
            <i class="bi bi-envelope-plus me-1" aria-hidden="true"></i>{{ __('New Invitation') }}
        </button>
    @endcan
@endsection

@section('content')
    <div id="inviteAjaxAlert" class="d-none mb-3"></div>

    <div id="inviteLinkBanner" class="alert alert-info py-2 px-3 small d-flex flex-wrap align-items-center justify-content-between gap-2 {{ session('invite_url') ? '' : 'd-none' }}">
        <div class="min-w-0 text-break">
            <strong>{{ __('Invitation Link:') }}</strong>
            <code id="generatedInviteUrl" class="ms-1">{{ session('invite_url') }}</code>
        </div>
        <button type="button" id="copyInviteUrlBtn" class="btn btn-sm btn-outline-primary flex-shrink-0">
            <i class="bi bi-clipboard me-1" aria-hidden="true"></i>{{ __('Copy Link') }}
        </button>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table
                id="invitationsDataTable"
                class="table blf-table table-hover mb-0 w-100"
                data-datatable-url="{{ route('admin.datatables.invitations') }}"
                data-datatable-total="{{ $invitations->total() }}"
                data-datatable-order-col="4"
                data-datatable-order-dir="desc"
            >
                <thead>
                    <tr>
                        <th>{{ __('Action') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Role') }}</th>
                        <th>{{ __('Station / Booth') }}</th>
                        <th>{{ __('Expires') }}</th>
                        <th>{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invitations as $invitation)
                        @php
                            $inviteState = match (true) {
                                $invitation->accepted_at !== null => 'approved',
                                $invitation->revoked_at !== null => 'revoked',
                                $invitation->expires_at->isPast() => 'cancelled',
                                default => 'pending',
                            };
                            $inviteLabel = match ($inviteState) {
                                'approved' => __('Accepted'),
                                'revoked' => __('Revoked'),
                                'cancelled' => __('Expired'),
                                default => __('Pending'),
                            };
                        @endphp
                        <tr>
                            <td class="text-nowrap">
                                @if ($inviteState === 'pending' && auth()->user()?->can('revoke', $invitation))
                                    <form method="POST" action="{{ route('admin.invitations.revoke', $invitation) }}" class="d-inline" data-confirm="{{ __('Revoke this pending invitation?') }}" data-confirm-title="{{ __('Revoke Invitation') }}" data-confirm-button="{{ __('Revoke') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Revoke') }}</button>
                                    </form>
                                @else
                                    <span class="small text-secondary">—</span>
                                @endif
                            </td>
                            <td class="fw-medium text-navy">{{ $invitation->email }}</td>
                            <td>{{ $invitation->role?->name }}</td>
                            <td>{{ $invitation->organization?->name ?? __('Platform-Wide') }}</td>
                            <td class="small text-secondary">{{ $invitation->expires_at->format('M d, Y H:i') }}</td>
                            <td><x-status-badge :status="$inviteState" :label="$inviteLabel" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($invitations->hasPages())
            <div class="card-footer bg-white py-2 px-3">
                {{ $invitations->links() }}
            </div>
        @endif
    </div>

    <div class="modal fade" id="createInvitationModal" tabindex="-1" aria-labelledby="createInvitationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="createInvitationForm" method="POST" action="{{ route('admin.invitations.store') }}" class="modal-content">
                @csrf
                <div class="modal-header py-2 px-3">
                    <h2 class="modal-title h6 text-navy" id="createInvitationModalLabel">{{ __('Issue Staff Invitation') }}</h2>
                    <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
                </div>
                <div class="modal-body p-3">
                    <div id="createInvitationError" class="alert alert-danger py-2 px-3 small d-none"></div>
                    <div class="mb-3">
                        <label for="inv_email" class="form-label small fw-medium">{{ __('Google Email Address') }}</label>
                        <input id="inv_email" name="email" type="email" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label for="inv_role" class="form-label small fw-medium">{{ __('Role') }}</label>
                        <select id="inv_role" name="role_key" class="form-select form-select-sm" required>
                            <option value="institution_staff">{{ __('Institution Staff') }}</option>
                            @if (auth()->user()?->isPlatformSuperAdmin())
                                <option value="institution_admin">{{ __('Institution Administrator') }}</option>
                                <option value="platform_super_admin">{{ __('Platform Super Administrator') }}</option>
                            @endif
                        </select>
                    </div>
                    @if ($organizations->isNotEmpty())
                        <div class="mb-3">
                            <label for="inv_org" class="form-label small fw-medium">{{ __('Police Station / Booth') }}</label>
                            <select id="inv_org" name="organization_id" class="form-select form-select-sm" data-select2-url="{{ route('api.select2.organizations') }}" data-placeholder="{{ __('Search station or booth...') }}">
                                <option value=""></option>
                                @if ($organizations->count() === 1 && ! auth()->user()?->isPlatformSuperAdmin())
                                    <option value="{{ $organizations->first()->id }}" selected>{{ $organizations->first()->name }}</option>
                                @endif
                            </select>
                        </div>
                    @endif
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" id="createInvitationSubmitBtn" class="btn btn-sm btn-primary">{{ __('Create Invitation') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@pushOnce('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const copyBtn = document.getElementById('copyInviteUrlBtn');
    const urlEl = document.getElementById('generatedInviteUrl');
    const linkBanner = document.getElementById('inviteLinkBanner');
    const pageAlert = document.getElementById('inviteAjaxAlert');
    const form = document.getElementById('createInvitationForm');
    const submitBtn = document.getElementById('createInvitationSubmitBtn');
    const errorBox = document.getElementById('createInvitationError');
    const modalEl = document.getElementById('createInvitationModal');

    copyBtn?.addEventListener('click', async () => {
        if (!urlEl) return;
        const text = urlEl.textContent.trim();
        try {
            await navigator.clipboard.writeText(text);
        } catch (_) {
            const temp = document.createElement('textarea');
            temp.value = text;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            temp.remove();
        }
        copyBtn.innerHTML = '<i class="bi bi-check2 me-1"></i>Copied!';
        setTimeout(() => {
            copyBtn.innerHTML = '<i class="bi bi-clipboard me-1"></i>Copy Link';
        }, 1800);
    });

    form?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!submitBtn) return;

        errorBox?.classList.add('d-none');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Sending...';

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const res = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: new FormData(form),
            });

            const data = await res.json();

            if (!res.ok) {
                const msg = data.message || Object.values(data.errors || {}).flat()[0] || 'Unable to create invitation.';
                if (errorBox) {
                    errorBox.textContent = msg;
                    errorBox.classList.remove('d-none');
                }
                return;
            }

            if (modalEl && window.bootstrap) {
                window.bootstrap.Modal.getOrCreateInstance(modalEl).hide();
            }

            form.reset();

            if (pageAlert) {
                pageAlert.className = `alert ${data.email_sent ? 'alert-success' : 'alert-warning'} py-2 px-3 small mb-3`;
                pageAlert.textContent = data.message || 'Invitation created.';
            }

            if (data.invite_url && urlEl && linkBanner) {
                urlEl.textContent = data.invite_url;
                linkBanner.classList.remove('d-none');
            }

            if (window.jQuery && window.jQuery.fn.DataTable && window.jQuery.fn.DataTable.isDataTable('#invitationsDataTable')) {
                window.jQuery('#invitationsDataTable').DataTable().ajax.reload(null, false);
            }
        } catch (_) {
            if (errorBox) {
                errorBox.textContent = 'Network error while creating invitation. Please try again.';
                errorBox.classList.remove('d-none');
            }
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Create Invitation';
        }
    });
});
</script>
@endPushOnce
