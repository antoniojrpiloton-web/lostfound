<?php

namespace App\Services\Matching;

use App\Contracts\Matching\ImageSimilarityContract;
use App\Models\ImageEmbedding;
use App\Models\ItemReport;

class ClipImageSimilarityProvider implements ImageSimilarityContract
{
    protected string $lastModelVersion = 'clip';

    protected array $embeddingCache = [];

    public function __construct(public VisualSearchService $visualSearch) {}

    public function isEnabled(): bool
    {
        return $this->visualSearch->isImageMatchingEnabled();
    }

    public function preloadForReportIds(array $reportIds): void
    {
        $missing = array_values(array_diff(array_unique($reportIds), array_keys($this->embeddingCache)));
        if ($missing === []) {
            return;
        }

        foreach ($missing as $id) {
            $this->embeddingCache[$id] = [];
        }

        $rows = ImageEmbedding::query()
            ->whereIn('item_report_id', $missing)
            ->get(['item_report_id', 'model_version', 'vector']);

        foreach ($rows as $row) {
            $this->embeddingCache[(int) $row->item_report_id][] = [
                'model_version' => (string) $row->model_version,
                'vector' => array_map('floatval', is_array($row->vector) ? $row->vector : []),
            ];
        }
    }

    public function similarity(ItemReport $lost, ItemReport $found): ?float
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $this->preloadForReportIds([$lost->id, $found->id]);

        $lostEmbeddings = $this->embeddingCache[$lost->id] ?? [];
        $foundEmbeddings = $this->embeddingCache[$found->id] ?? [];

        if ($lostEmbeddings === [] || $foundEmbeddings === []) {
            return null;
        }

        $best = null;

        foreach ($lostEmbeddings as $lostEmb) {
            foreach ($foundEmbeddings as $foundEmb) {
                if ($lostEmb['model_version'] !== $foundEmb['model_version']) {
                    continue;
                }

                $sim = $this->visualSearch->cosineSimilarity(
                    $lostEmb['vector'],
                    $foundEmb['vector'],
                );

                if ($best === null || $sim > $best) {
                    $best = $sim;
                    $this->lastModelVersion = $lostEmb['model_version'];
                }
            }
        }

        return $best;
    }

    public function modelVersion(): string
    {
        return $this->lastModelVersion;
    }
}
