<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Default values for every backoffice-editable setting. Admins tune these
     * afterwards via GET/PATCH /api/admin/settings — no redeploy needed.
     */
    public function run(): void
    {
        $defaults = [
            [
                'key' => 'commission_rate_order',
                'value' => '0.10',
                'type' => 'decimal',
                'group' => 'commerce',
                'description' => 'Taux de commission plateforme prélevé sur le sous-total produits de chaque commande.',
            ],
            [
                'key' => 'commission_rate_delivery',
                'value' => '0.15',
                'type' => 'decimal',
                'group' => 'commerce',
                'description' => 'Taux de commission plateforme prélevé sur les frais de livraison de chaque course.',
            ],
            [
                'key' => 'delivery_base_fee',
                'value' => '300',
                'type' => 'decimal',
                'group' => 'delivery',
                'description' => 'Frais de livraison fixe de départ (XOF), appliqué à chaque course quelle que soit la distance.',
            ],
            [
                'key' => 'delivery_rate_per_km',
                'value' => '150',
                'type' => 'decimal',
                'group' => 'delivery',
                'description' => 'Tarif kilométrique (XOF/km) ajouté au frais de base selon la distance vendeur → client.',
            ],
            [
                'key' => 'delivery_min_fee',
                'value' => '500',
                'type' => 'decimal',
                'group' => 'delivery',
                'description' => 'Frais de livraison plancher (XOF), utilisé aussi en repli si les coordonnées GPS manquent.',
            ],
            [
                'key' => 'delivery_max_fee',
                'value' => '5000',
                'type' => 'decimal',
                'group' => 'delivery',
                'description' => 'Frais de livraison plafond (XOF). 0 = pas de plafond.',
            ],
            [
                'key' => 'paygate_fee_flooz',
                'value' => '0.025',
                'type' => 'decimal',
                'group' => 'paygate',
                'description' => 'Taux de frais PayGate sur les transactions Flooz (informatif, non répercuté sur vendeur/livreur).',
            ],
            [
                'key' => 'paygate_fee_tmoney',
                'value' => '0.03',
                'type' => 'decimal',
                'group' => 'paygate',
                'description' => 'Taux de frais PayGate sur les transactions TMoney (informatif, non répercuté sur vendeur/livreur).',
            ],
            [
                'key' => 'property_free_quota',
                'value' => '3',
                'type' => 'integer',
                'group' => 'immobilier',
                'description' => 'Nombre d\'annonces qu\'un propriétaire non abonné peut publier gratuitement.',
            ],
            [
                'key' => 'job_offer_free_quota',
                'value' => '3',
                'type' => 'integer',
                'group' => 'emploi',
                'description' => 'Nombre d\'offres qu\'un recruteur non abonné peut publier gratuitement.',
            ],
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
            Setting::updateOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
