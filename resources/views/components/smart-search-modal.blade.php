@php
    $authUser = auth()->user();
    $isInstitutional = $authUser?->isInstitutionalUser() ?? false;
    $visualSearchEnabled = app(App\Services\Matching\VisualSearchService::class)->isVisualSearchEnabled();
@endphp

<div class="modal fade" id="blfSmartSearchModal" tabindex="-1" aria-labelledby="blfSmartSearchLabel" aria-hidden="true" data-smart-search-url="{{ route('api.smart_search') }}">
    <div id="blfSmartSearchDialog" class="modal-dialog modal-lg modal-dialog-scrollable" style="transition: max-width 0.2s ease;">
        <div class="modal-content border-0 shadow-lg overflow-hidden" style="border-radius: 1rem;">
            <div class="modal-header border-bottom py-3 px-4" style="background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);">
                <div class="d-flex align-items-center gap-2">
                    <span class="blf-brand-square" style="width: 30px; height: 30px; font-size: 0.85rem;">
                        <i class="bi bi-stars" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 class="modal-title h6 fw-bold text-navy mb-0" id="blfSmartSearchLabel">{{ __('Smart System Search') }}</h2>
                        <div class="text-secondary" style="font-size: 0.72rem;">{{ __('Search lost & found items by keyword, reference no., or photo') }}</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>

            <div class="modal-body p-4">
                <div id="smartSearchInputView">
                <ul class="nav nav-pills nav-fill gap-2 mb-3 p-1 rounded-3 bg-light border" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active py-1 small fw-medium" id="smartTabText" data-bs-toggle="pill" data-bs-target="#smartPaneText" type="button" role="tab">
                            <i class="bi bi-search me-1" aria-hidden="true"></i>{{ __('Smart Text Search') }}
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link py-1 small fw-medium" id="smartTabPhoto" data-bs-toggle="pill" data-bs-target="#smartPanePhoto" type="button" role="tab">
                            <i class="bi bi-camera me-1" aria-hidden="true"></i>{{ __('Photo Deep Search') }}
                            @if (! $visualSearchEnabled)
                                <span class="badge bg-secondary-subtle text-secondary ms-1">OFF</span>
                            @endif
                        </button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="smartPaneText" role="tabpanel">
                        <div class="row g-2 mb-3">
                            <div class="col-md-7">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-primary"><i class="bi bi-search" aria-hidden="true"></i></span>
                                    <input type="search" id="smartSearchInput" class="form-control" placeholder="{{ __('Type item name, brand, color, location, or BLF-...') }}" autocomplete="off">
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <select id="smartSearchType" class="form-select form-select-sm">
                                    <option value="">{{ __('Lost & Found') }}</option>
                                    <option value="found">{{ __('Found Only') }}</option>
                                    <option value="lost">{{ __('Lost Only') }}</option>
                                </select>
                            </div>
                            @if ($isInstitutional)
                                <div class="col-6 col-md-2">
                                    <select id="smartSearchScope" class="form-select form-select-sm">
                                        <option value="org">{{ __('All Org') }}</option>
                                        <option value="public">{{ __('Public') }}</option>
                                    </select>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="tab-pane fade" id="smartPanePhoto" role="tabpanel">
                        @if ($visualSearchEnabled)
                            <form id="smartPhotoForm" enctype="multipart/form-data">
                                <div class="mb-2">
                                    <x-dropzone
                                        id="smart_search_photo"
                                        name="photos[]"
                                        :hint="__('Drag & drop up to 5 photos (JPG, PNG, WEBP, max 5MB each) to deep-search matching items')"
                                        accept="image/jpeg,image/png,image/webp"
                                        :multiple="true"
                                        :max-files="5"
                                        :compact="true"
                                    />
                                </div>
                                <div class="row g-2 align-items-center mb-3">
                                    <div class="{{ $isInstitutional ? 'col-md-5' : 'col-md-7' }}">
                                        <select id="smartPhotoType" name="type" class="form-select form-select-sm">
                                            <option value="">{{ __('Search All Lost & Found Items') }}</option>
                                            <option value="found">{{ __('Search Found Items Only') }}</option>
                                            <option value="lost">{{ __('Search Lost Items Only') }}</option>
                                        </select>
                                    </div>
                                    @if ($isInstitutional)
                                        <div class="col-md-3">
                                            <select id="smartPhotoScope" name="scope" class="form-select form-select-sm">
                                                <option value="org">{{ __('All Org (incl. Pending)') }}</option>
                                                <option value="public">{{ __('Public Verified Only') }}</option>
                                            </select>
                                        </div>
                                    @endif
                                    <div class="{{ $isInstitutional ? 'col-md-4' : 'col-md-5' }}">
                                        <button type="submit" id="smartPhotoSubmitBtn" class="btn btn-sm btn-primary w-100">
                                            <i class="bi bi-camera me-1" aria-hidden="true"></i>{{ __('Run Photo Deep Search') }}
                                        </button>
                                    </div>
                                </div>
                            </form>
                        @else
                            <div class="alert alert-info small mb-3">
                                <i class="bi bi-info-circle me-1" aria-hidden="true"></i>
                                {{ __('Visual Photo Deep Search is currently disabled. Platform administrators can enable it anytime in System Settings.') }}
                            </div>
                        @endif
                    </div>
                </div>
                </div>

                <div id="smartResultsHeader" class="d-none d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">
                    <div>
                        <span class="blf-status-pill blf-pill-primary mb-1">{{ __('Visual Match Results') }}</span>
                        <div id="smartResultsHeadline" class="h6 fw-bold text-navy mb-0"></div>
                    </div>
                    <button type="button" id="smartBackToSearchBtn" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>{{ __('New Search / Upload Again') }}
                    </button>
                </div>

                <div id="smartSearchStatus" class="small text-secondary mb-2"></div>
                <div id="smartSearchResults" class="row g-3 d-none"></div>

                <div class="mt-3 pt-3 border-top">
                    <div class="blf-page-eyebrow mb-2">{{ __('Quick Actions') }}</div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('public.items.index') }}" class="btn btn-sm btn-light border text-navy">
                            <i class="bi bi-grid me-1 text-primary" aria-hidden="true"></i>{{ __('Browse All Items') }}
                        </a>
                        <a href="{{ route('reports.lost.create') }}" class="btn btn-sm btn-light border text-navy">
                            <i class="bi bi-megaphone me-1 text-primary" aria-hidden="true"></i>{{ __('Report Lost Item') }}
                        </a>
                        <a href="{{ route('reports.found.create') }}" class="btn btn-sm btn-light border text-navy">
                            <i class="bi bi-box-seam me-1 text-primary" aria-hidden="true"></i>{{ __('Report Found Item') }}
                        </a>
                        @if ($isInstitutional)
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-light border text-navy">
                                <i class="bi bi-clipboard-check me-1 text-primary" aria-hidden="true"></i>{{ __('Reports Queue') }}
                            </a>
                            <a href="{{ route('admin.found.create') }}" class="btn btn-sm btn-light border text-navy">
                                <i class="bi bi-plus-circle me-1 text-primary" aria-hidden="true"></i>{{ __('Desk Found Record') }}
                            </a>
                            <a href="{{ route('admin.analytics.index') }}" class="btn btn-sm btn-light border text-navy">
                                <i class="bi bi-bar-chart-line me-1 text-primary" aria-hidden="true"></i>{{ __('Analytics') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
