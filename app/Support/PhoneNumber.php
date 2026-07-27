<?php

namespace App\Support;

/**
 * Normalisation des numéros de téléphone togolais.
 *
 * Les numéros sont saisis sous des formes très variées (+228 90 11 22 33,
 * 00228-90112233, 90 11 22 33…). Les partenaires attendent des formats précis :
 * PayGate veut le numéro local à 8 chiffres, AfrikSMS le numéro international
 * sans « + » ni « 00 » (22890112233).
 */
class PhoneNumber
{
    /** Indicatif pays par défaut (Togo). */
    public const DEFAULT_COUNTRY_CODE = '228';

    /**
     * Ne garde que les chiffres.
     */
    public static function digits(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    /**
     * Forme locale à 8 chiffres (90112233), ou la saisie nettoyée si le numéro
     * n'est pas un numéro togolais reconnaissable.
     */
    public static function local(?string $phone, string $countryCode = self::DEFAULT_COUNTRY_CODE): string
    {
        $digits = self::digits($phone);

        // 00228… -> 228…
        if (str_starts_with($digits, '00'.$countryCode)) {
            $digits = substr($digits, 2);
        }

        if (strlen($digits) > 8 && str_starts_with($digits, $countryCode)) {
            return substr($digits, strlen($countryCode));
        }

        return $digits;
    }

    /**
     * Forme internationale sans préfixe (22890112233), telle qu'attendue par AfrikSMS.
     */
    public static function international(?string $phone, string $countryCode = self::DEFAULT_COUNTRY_CODE): string
    {
        $local = self::local($phone, $countryCode);

        return $local === '' ? '' : $countryCode.$local;
    }
}
