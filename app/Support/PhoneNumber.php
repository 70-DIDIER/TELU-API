<?php

namespace App\Support;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

/**
 * Normalisation des numéros de téléphone.
 *
 * `local()`/`international()` sont togolais uniquement — utilisés pour le
 * mobile money (PayGate veut le numéro local à 8 chiffres, AfrikSMS le
 * numéro international sans « + » ni « 00 », 22890112233) : T-Money/Flooz
 * n'opèrent que sur le réseau togolais, aucune raison de les faire évoluer.
 *
 * `e164()` est la version multi-pays utilisée pour le numéro de *compte*
 * (inscription, connexion, OTP) — voir App\Services\OtpService.
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

    /**
     * Forme E.164 sans « + » (22890112233, 33612345678…), pour un numéro de
     * *compte* de n'importe quel pays. `$defaultRegion` (indicatif ISO 3166-1
     * alpha-2, "TG" par défaut) ne s'applique qu'aux saisies sans indicatif
     * explicite.
     *
     * Renvoie une chaîne vide si le numéro n'est pas reconnu comme valide.
     */
    public static function e164(?string $phone, string $defaultRegion = 'TG'): string
    {
        $raw = trim((string) $phone);

        if ($raw === '') {
            return '';
        }

        if (str_starts_with($raw, '+')) {
            return self::parse($raw, null);
        }

        $digits = self::digits($raw);

        // Forme historique déjà produite partout avant le support multi-pays
        // (228 + 8 chiffres) : passthrough pour ne rien casser sur les lignes
        // déjà en base.
        if (str_starts_with($digits, self::DEFAULT_COUNTRY_CODE)
            && strlen($digits) === strlen(self::DEFAULT_COUNTRY_CODE) + 8) {
            return $digits;
        }

        return self::parse($digits, $defaultRegion);
    }

    private static function parse(string $number, ?string $defaultRegion): string
    {
        $util = PhoneNumberUtil::getInstance();

        try {
            $proto = $util->parse($number, $defaultRegion);
        } catch (NumberParseException) {
            return '';
        }

        if (! $util->isValidNumber($proto)) {
            return '';
        }

        return ltrim($util->format($proto, PhoneNumberFormat::E164), '+');
    }
}
