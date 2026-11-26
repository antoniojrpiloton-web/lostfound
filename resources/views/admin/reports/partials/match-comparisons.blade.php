<div class="row g-2 mb-3 small">
    <div class="col-sm-4">
        <div class="p-2 rounded-3 border bg-light h-100">
            <div class="text-secondary" style="font-size: 0.72rem;">{{ __('Possible Matches') }}</div>
            <div class="fw-bold text-navy fs-6">
                {{ count($matchComparisons) }}
                @if (! empty($matchComparisons[0]))
                    <span class="badge bg-primary-subtle text-primary ms-1">{{ $matchComparisons[0]['overall_score'] }}% {{ __('top') }}</span>
                @endif
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="p-2 rounded-3 border bg-light h-100">
            <div class="text-secondary" style="font-size: 0.72rem;">{{ __('Ownership Claims') }}</div>
            <div class="fw-bold text-navy fs-6">{{ $report->claims->count() }}</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="p-2 rounded-3 border bg-light h-100">
            <div class="text-secondary" style="font-size: 0.72rem;">{{ __('Custody Age') }}</div>
            <div class="fw-bold text-navy fs-6">
                {{ $custodyAgeDays }}d
                @if ($report->type === App\Enums\ReportType::Found && ! $isCompleted)
                    <span class="small fw-normal text-secondary">({{ $daysUntilDisposal }}d {{ __('left') }})</span>
                @endif
            </div>
        </div>
    </div>
</div>

@if ($matchComparisons !== [])
    <div class="d-flex flex-column gap-3">
        @foreach ($matchComparisons as $entry)
            @php
                $counterpart = $entry['counterpart'];
                $factors = $entry['factors'];
                $visual = $entry['visual'];
                $srcImg = ($visual['source_image_id'] ?? null)
                    ? $report->images->firstWhere('id', $visual['source_image_id'])
                    : $report->images->sortByDesc('is_primary')->first();
                $tgtImg = ($visual['target_image_id'] ?? null)
                    ? $counterpart->images->firstWhere('id', $visual['target_image_id'])
                    : $counterpart->images->sortByDesc('is_primary')->first();
                $isCurrentFound = $report->type === App\Enums\ReportType::Found;
            @endphp
            <div class="border rounded-3 p-3 bg-white shadow-sm">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                    <div class="min-w-0">
                        <span class="badge {{ $counterpart->type === App\Enums\ReportType::Lost ? 'bg-warning-subtle text-warning-emphasis' : 'bg-info-subtle text-info-emphasis' }} me-1">
                            {{ ucfirst($counterpart->type->value) }}
                        </span>
                        <span class="fw-bold text-navy">{{ $counterpart->item_name }}</span>
                        <code class="ms-1 text-primary small">{{ $counterpart->reference_no }}</code>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-primary">{{ $entry['overall_score'] }}% {{ __('Overall Match') }}</span>
                        <a href="{{ route('admin.reports.show', $counterpart) }}" class="btn btn-sm btn-outline-primary py-0 px-2">{{ __('Inspect') }}</a>
                    </div>
                </div>

                @if ($srcImg || $tgtImg)
                    <div class="row g-2 align-items-center my-2 p-2 rounded-3 border" style="background: #f8fbff;">
                        <div class="col-5 text-center">
                            <div class="small fw-semibold text-navy mb-1">
                                {{ $isCurrentFound ? __('Found Item Photo') : __('Lost Item Photo') }}
                            </div>
                            @if ($srcImg)
                                <a href="#reportImageModal" data-bs-toggle="modal" data-preview-src="{{ route('admin.images.preview', $srcImg) }}" class="d-inline-block w-100">
                                    <img src="{{ route('admin.images.preview', $srcImg) }}" alt="" class="img-fluid rounded border bg-white" style="height: 120px; width: 100%; object-fit: cover;">
                                </a>
                            @else
                                <div class="rounded border bg-white d-flex align-items-center justify-content-center text-secondary small" style="height: 120px;">{{ __('No photo') }}</div>
                            @endif
                        </div>

                        <div class="col-2 text-center">
                            <div class="small text-secondary mb-1"><i class="bi bi-arrow-left-right text-primary" aria-hidden="true"></i></div>
                            @if ($visual)
                                <span class="badge bg-primary-subtle text-primary d-inline-block">{{ $visual['overall'] }}%</span>
                                <div class="text-secondary" style="font-size: 0.66rem;">{{ __('Visual') }}</div>
                            @else
                                <span class="badge bg-light text-secondary border">—</span>
                            @endif
                        </div>

                        <div class="col-5 text-center">
                            <div class="small fw-semibold text-navy mb-1">
                                {{ $isCurrentFound ? __('Matched Lost Photo') : __('Matched Found Photo') }}
                            </div>
                            @if ($tgtImg)
                                <a href="#reportImageModal" data-bs-toggle="modal" data-preview-src="{{ route('admin.images.preview', $tgtImg) }}" class="d-inline-block w-100">
                                    <img src="{{ route('admin.images.preview', $tgtImg) }}" alt="" class="img-fluid rounded border bg-white" style="height: 120px; width: 100%; object-fit: cover;">
                                </a>
                            @else
                                <div class="rounded border bg-white d-flex align-items-center justify-content-center text-secondary small" style="height: 120px;">{{ __('No photo') }}</div>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($visual && ! empty($visual['breakdown']))
                    @php $vb = $visual['breakdown']; @endphp
                    <div class="mt-2">
                        <div class="text-secondary mb-1" style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
                            {{ __('Image Similarity Breakdown') }}
                        </div>
                        <div class="d-flex flex-wrap gap-1" style="font-size: 0.72rem;">
                            @if (($vb['engine'] ?? '') === 'exact')
                                <span class="badge bg-success-subtle text-success-emphasis">{{ __('Exact Photo Match: 100%') }}</span>
                            @elseif (isset($vb['color']))
                                <span class="badge bg-primary-subtle text-primary">{{ __('Color:') }} <strong>{{ $vb['color'] }}%</strong></span>
                                <span class="badge bg-primary-subtle text-primary">{{ __('Shape & Edges:') }} <strong>{{ $vb['shape'] }}%</strong></span>
                                <span class="badge bg-primary-subtle text-primary">{{ __('Layout:') }} <strong>{{ $vb['layout'] }}%</strong></span>
                            @elseif (isset($vb['semantic']))
                                <span class="badge bg-primary-subtle text-primary">{{ __('AI Semantic Vision:') }} <strong>{{ $vb['semantic'] }}%</strong></span>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($factors !== [])
                    <div class="mt-2">
                        <div class="text-secondary mb-1" style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em;">
                            {{ __('Metadata Similarity Breakdown') }}
                        </div>
                        <div class="d-flex flex-wrap gap-1" style="font-size: 0.72rem;">
                            @foreach (['category' => 'Category', 'item_name' => 'Item Name', 'description' => 'Description', 'brand' => 'Brand', 'model' => 'Model', 'color' => 'Color', 'location' => 'Location', 'date' => 'Date', 'image' => 'Visual'] as $fKey => $fLabel)
                                @if (isset($factors[$fKey]) && (float) $factors[$fKey] > 0)
                                    <span class="badge bg-light text-secondary border">{{ __($fLabel) }}: <strong class="text-navy">{{ (int) round((float) $factors[$fKey] * 100) }}%</strong></span>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="text-secondary mt-2 pt-2 border-top d-flex flex-wrap justify-content-between gap-2" style="font-size: 0.74rem;">
                    <span>{{ $counterpart->category?->label() }} · {{ $counterpart->location?->name ?: ($counterpart->location_details ?: 'Boracay Island') }} · {{ $counterpart->occurred_date?->format('M d, Y') }}</span>
                    <span>{{ __('Status:') }} <strong class="text-navy">{{ ucfirst(str_replace('_', ' ', $counterpart->item_status->value)) }}</strong></span>
                </div>
            </div>
        @endforeach
    </div>
@else
    <div class="small text-secondary">
        <i class="bi bi-info-circle text-primary me-1" aria-hidden="true"></i>
        {{ __('No counterpart matches detected yet. Click Scan Matches Now or use Ctrl+K Photo Deep Search to inspect similar items.') }}
    </div>
@endif
