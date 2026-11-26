<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'match.min_score', 'group' => 'matching', 'value' => ['value' => 0.55]],
            ['key' => 'match.high_score', 'group' => 'matching', 'value' => ['value' => 0.75]],
            ['key' => 'match.date_window_before_days', 'group' => 'matching', 'value' => ['value' => 2]],
            ['key' => 'match.date_window_after_days', 'group' => 'matching', 'value' => ['value' => 90]],
            ['key' => 'matching.image_enabled', 'group' => 'matching', 'value' => ['value' => false]],
            ['key' => 'matching.visual_search_enabled', 'group' => 'matching', 'value' => ['value' => false]],
            ['key' => 'matching.visual_search_min_score', 'group' => 'matching', 'value' => ['value' => 0.64]],
            [
                'key' => 'match.weights',
                'group' => 'matching',
                'value' => [
                    'category' => 0.20,
                    'item_name' => 0.15,
                    'description' => 0.20,
                    'brand' => 0.10,
                    'model' => 0.05,
                    'color' => 0.10,
                    'location' => 0.10,
                    'date' => 0.10,
                ],
            ],
            ['key' => 'image_validation.fail_mode', 'group' => 'image_validation', 'value' => ['value' => 'review']],
            ['key' => 'image_validation.blur_reject_threshold', 'group' => 'image_validation', 'value' => ['value' => 80]],
            ['key' => 'image_validation.blur_review_threshold', 'group' => 'image_validation', 'value' => ['value' => 120]],
            ['key' => 'image_validation.dark_mean_threshold', 'group' => 'image_validation', 'value' => ['value' => 45]],
            ['key' => 'image_validation.bright_mean_threshold', 'group' => 'image_validation', 'value' => ['value' => 220]],
            ['key' => 'image_validation.object_reject_threshold', 'group' => 'image_validation', 'value' => ['value' => 0.35]],
            ['key' => 'image_validation.object_review_threshold', 'group' => 'image_validation', 'value' => ['value' => 0.55]],
            ['key' => 'disposal.retention_days', 'group' => 'disposal', 'value' => ['value' => 180]],
            ['key' => 'institutions.admins_can_manage_staff', 'group' => 'institutions', 'value' => ['value' => true]],
            ['key' => 'notifications.email_enabled', 'group' => 'notifications', 'value' => ['value' => false]],
            ['key' => 'translation.content_enabled', 'group' => 'localization', 'value' => ['value' => false]],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'group' => $setting['group'],
                    'value' => $setting['value'],
                ]
            );
        }
    }
}
