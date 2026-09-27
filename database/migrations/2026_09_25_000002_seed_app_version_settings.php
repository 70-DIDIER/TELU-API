<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Bootstraps the app-version Setting rows via a migration rather than the
 * SettingSeeder, because a prod deploy only runs `migrate` — re-running the
 * full seeder in prod would overwrite every already-tuned setting
 * (commission rates, quotas...) back to its seeded default. firstOrCreate
 * keeps this idempotent and additive: existing rows are left untouched.
 */
return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            [
                'key' => 'min_app_version_ios',
                'value' => '1.0.0',
                'type' => 'string',
                'group' => 'app',
                'description' => "Version iOS minimale acceptée (semver). En dessous, l'app affiche un écran de mise à jour forcée.",
            ],
            [
                'key' => 'min_app_version_android',
                'value' => '1.0.0',
                'type' => 'string',
                'group' => 'app',
                'description' => "Version Android minimale acceptée (semver). En dessous, l'app affiche un écran de mise à jour forcée.",
            ],
            [
                'key' => 'latest_app_version_ios',
                'value' => '1.0.0',
                'type' => 'string',
                'group' => 'app',
                'description' => 'Dernière version iOS publiée (semver) — sert à une simple invitation à mettre à jour, non bloquante.',
            ],
            [
                'key' => 'latest_app_version_android',
                'value' => '1.0.0',
                'type' => 'string',
                'group' => 'app',
                'description' => 'Dernière version Android publiée (semver) — sert à une simple invitation à mettre à jour, non bloquante.',
            ],
            [
                'key' => 'app_store_url_ios',
                'value' => '',
                'type' => 'string',
                'group' => 'app',
                'description' => "Lien App Store affiché sur l'écran de mise à jour (bouton).",
            ],
            [
                'key' => 'app_store_url_android',
                'value' => 'https://play.google.com/store/apps/details?id=com.duokhorus.telu',
                'type' => 'string',
                'group' => 'app',
                'description' => "Lien Play Store affiché sur l'écran de mise à jour (bouton).",
            ],
            [
                'key' => 'app_update_message',
                'value' => '',
                'type' => 'string',
                'group' => 'app',
                'description' => "Message optionnel affiché sur l'écran de mise à jour forcée (vide = message par défaut côté app).",
            ],
        ];

        foreach ($defaults as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }

    public function down(): void
    {
        Setting::whereIn('key', [
            'min_app_version_ios',
            'min_app_version_android',
            'latest_app_version_ios',
            'latest_app_version_android',
            'app_store_url_ios',
            'app_store_url_android',
            'app_update_message',
        ])->delete();
    }
};
