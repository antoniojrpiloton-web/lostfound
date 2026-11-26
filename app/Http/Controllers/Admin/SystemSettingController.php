<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSystemSettingRequest;
use App\Models\SystemSetting;
use App\Services\Audit\AuditService;
use App\Services\MasterData\MasterDataCacheService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function __construct(
        public AuditService $auditService,
        public MasterDataCacheService $masterDataCache,
    ) {}

    public function index(): View
    {
        Gate::authorize('viewAny', SystemSetting::class);

        $groupedSettings = SystemSetting::query()
            ->orderBy('group')
            ->orderBy('key')
            ->get()
            ->groupBy('group');

        return view('admin.masterdata.settings', compact('groupedSettings'));
    }

    public function update(UpdateSystemSettingRequest $request, SystemSetting $setting): RedirectResponse
    {
        $rawValue = $request->input('value');
        $oldValue = $setting->value;

        $newPayload = $this->normalizeSettingValue($setting->value, $rawValue);

        $setting->update([
            'value' => $newPayload,
            'updated_by' => $request->user()?->id,
        ]);

        $this->masterDataCache->forgetSettings();

        $this->auditService->record(
            action: 'masterdata.setting_updated',
            auditable: $setting,
            oldValues: ['key' => $setting->key, 'value' => $oldValue],
            newValues: ['key' => $setting->key, 'value' => $newPayload],
            actor: $request->user(),
            request: $request,
        );

        return redirect()->route('admin.settings.index')->with('status', __('System setting updated.'));
    }

    protected function normalizeSettingValue(?array $current, mixed $input): array
    {
        if (is_array($current) && array_key_exists('value', $current) && count($current) === 1) {
            $existing = $current['value'];

            if (is_bool($existing)) {
                return ['value' => filter_var($input, FILTER_VALIDATE_BOOLEAN)];
            }

            if (is_int($existing)) {
                return ['value' => (int) $input];
            }

            if (is_float($existing)) {
                return ['value' => (float) $input];
            }

            return ['value' => (string) $input];
        }

        if (is_string($input)) {
            $decoded = json_decode($input, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return is_array($input) ? $input : ['value' => $input];
    }
}
