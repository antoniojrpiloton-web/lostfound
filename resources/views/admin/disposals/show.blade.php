@extends('layouts.admin')

@section('title', __('Disposal Review').' — '.$disposal->itemReport?->reference_no)

@section('breadcrumbs')
    <x-breadcrumbs :items="[
        ['label' => __('Dashboard'), 'url' => route('admin.dashboard')],
        ['label' => __('Disposal Monitoring'), 'url' => route('admin.disposals.index')],
        ['label' => $disposal->itemReport?->reference_no ?? '#'.$disposal->id],
    ]" />
@endsection

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <div class="blf-page-eyebrow">{{ __('Structured Disposal Workflow') }}</div>
            <h1 class="h4 text-navy fw-bold mb-0">
                {{ $disposal->itemReport?->item_name }}
                <code class="fs-6 ms-1">{{ $disposal->itemReport?->reference_no }}</code>
            </h1>
        </div>
        <a href="{{ route('admin.disposals.index') }}" class="btn btn-sm btn-light bg-white border text-navy">
            <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>{{ __('Back to Queue') }}
        </a>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="blf-panel-card p-4 mb-3">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="h6 text-navy mb-0">{{ __('Disposal Record Summary') }}</h2>
                    <x-status-badge :status="$disposal->status" />
                </div>

                <dl class="row small mb-0">
                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Institution') }}</dt>
                    <dd class="col-sm-8">{{ $disposal->organization?->name }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Item Status') }}</dt>
                    <dd class="col-sm-8"><x-status-badge :status="$disposal->itemReport?->item_status" /></dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Date Found') }}</dt>
                    <dd class="col-sm-8">{{ $disposal->itemReport?->occurred_date?->format('F d, Y') }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Eligible On') }}</dt>
                    <dd class="col-sm-8">{{ $disposal->eligible_on?->format('F d, Y') }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Method') }}</dt>
                    <dd class="col-sm-8">{{ $methods[$disposal->method] ?? ($disposal->method ?: '—') }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Proposed By') }}</dt>
                    <dd class="col-sm-8">{{ $disposal->proposer?->name ?? '—' }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Approved By') }}</dt>
                    <dd class="col-sm-8">
                        {{ $disposal->approver?->name ?? '—' }}
                        @if ($disposal->approved_at)
                            <span class="text-secondary">({{ $disposal->approved_at->format('M d, Y H:i') }})</span>
                        @endif
                    </dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Disposed On') }}</dt>
                    <dd class="col-sm-8">{{ $disposal->disposed_on?->format('F d, Y') ?? '—' }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">{{ __('Remarks') }}</dt>
                    <dd class="col-sm-8">{{ $disposal->remarks ?: '—' }}</dd>
                </dl>
            </div>

            @if ($disposal->documents->isNotEmpty())
                <div class="blf-panel-card p-3">
                    <h2 class="h6 text-navy mb-2">{{ __('Supporting Documents') }}</h2>
                    <ul class="list-group list-group-flush small">
                        @foreach ($disposal->documents as $document)
                            <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                <span><i class="bi bi-file-earmark-text me-1" aria-hidden="true"></i>{{ $document->original_name }}</span>
                                <a href="{{ route('admin.disposals.document', [$disposal, $document]) }}" class="btn btn-sm btn-outline-primary">
                                    {{ __('Download') }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>

        <div class="col-lg-5">
            @if ($disposal->status === App\Enums\DisposalStatus::Eligible && auth()->user()?->can('propose', $disposal))
                <div class="blf-panel-card p-4 mb-3">
                    <h2 class="h6 text-navy mb-3">{{ __('Step 1: Propose Disposal') }}</h2>
                    <form method="POST" action="{{ route('admin.disposals.propose', $disposal) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label for="method" class="form-label small fw-semibold">{{ __('Disposal Method') }}</label>
                            <select id="method" name="method" class="form-select form-select-sm" required>
                                @foreach ($methods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="remarks" class="form-label small fw-semibold">{{ __('Remarks / Justification') }}</label>
                            <textarea id="remarks" name="remarks" rows="3" class="form-control form-control-sm"></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary w-100">{{ __('Submit Disposal Proposal') }}</button>
                    </form>
                </div>
            @endif

            @if ($disposal->status === App\Enums\DisposalStatus::Proposed && auth()->user()?->can('approve', $disposal))
                <div class="blf-panel-card p-4 mb-3">
                    <h2 class="h6 text-navy mb-3">{{ __('Step 2: Approve Disposal') }}</h2>
                    <form method="POST" action="{{ route('admin.disposals.approve', $disposal) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label for="approve_remarks" class="form-label small fw-semibold">{{ __('Approval Notes (Optional)') }}</label>
                            <textarea id="approve_remarks" name="remarks" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-success w-100">{{ __('Approve for Disposal') }}</button>
                    </form>
                </div>
            @endif

            @if ($disposal->status === App\Enums\DisposalStatus::Approved && auth()->user()?->can('complete', $disposal))
                <div class="blf-panel-card p-4 mb-3">
                    <h2 class="h6 text-navy mb-3">{{ __('Step 3: Record Completed Disposal') }}</h2>
                    <form method="POST" action="{{ route('admin.disposals.complete', $disposal) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label for="disposed_on" class="form-label small fw-semibold">{{ __('Date Disposed') }}</label>
                            <input type="date" id="disposed_on" name="disposed_on" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" class="form-control form-control-sm" required>
                        </div>
                        <div class="mb-3">
                            <label for="complete_method" class="form-label small fw-semibold">{{ __('Method Executed') }}</label>
                            <select id="complete_method" name="method" class="form-select form-select-sm" required>
                                @foreach ($methods as $key => $label)
                                    <option value="{{ $key }}" @selected($disposal->method === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <x-dropzone
                                id="documents"
                                name="documents[]"
                                :label="__('Supporting Documents (PDF / Photo, optional)')"
                                :hint="__('Drag & drop certificates, turnover memos, or disposal photos (up to 5 files, max 5MB each)')"
                                accept=".pdf,.jpg,.jpeg,.png,.webp"
                                :multiple="true"
                                :compact="true"
                                :max-files="5"
                            />
                        </div>
                        <div class="mb-3">
                            <label for="complete_remarks" class="form-label small fw-semibold">{{ __('Final Remarks') }}</label>
                            <textarea id="complete_remarks" name="remarks" rows="2" class="form-control form-control-sm"></textarea>
                        </div>
                        <button type="submit" class="btn btn-sm btn-danger w-100">{{ __('Complete Disposal & Update Custody') }}</button>
                    </form>
                </div>
            @endif

            @if (in_array($disposal->status, [App\Enums\DisposalStatus::Eligible, App\Enums\DisposalStatus::Proposed, App\Enums\DisposalStatus::Approved], true) && auth()->user()?->can('cancel', $disposal))
                <div class="blf-panel-card p-3">
                    <h2 class="h6 text-secondary mb-2">{{ __('Cancel Disposal') }}</h2>
                    <form method="POST" action="{{ route('admin.disposals.cancel', $disposal) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-2">
                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="{{ __('Reason for returning item to active custody') }}" required>
                        </div>
                        <button type="submit" class="btn btn-sm btn-outline-secondary w-100">{{ __('Cancel Disposal') }}</button>
                    </form>
                </div>
            @endif
        </div>
    </div>
@endsection
