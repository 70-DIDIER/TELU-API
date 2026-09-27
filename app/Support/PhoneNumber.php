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
     * true si `$e164` (forme E.164 sans "+", voir e164()) est un numéro togolais.
     * AfrikSMS ne couvre que le Togo : sert à décider SMS vs email pour l'OTP
     * (voir OtpService) et à savoir si les repêchages de formats historiques
     * (local à 8 chiffres, "+" superflu) sont pertinents.
     */
    public static function isTogo(string $e164): bool
    {
        return str_starts_with($e164, self::DEFAULT_COUNTRY_CODE);
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

        // Idempotence : cette fonction ne renvoie jamais de "+", donc rappeler
        // e164() sur sa propre sortie (ex. OtpService::issue() qui reçoit déjà
        // un numéro normalisé) doit redonner le même résultat. Sans ce détour,
        // un numéro étranger déjà normalisé (ex. "33612345678") serait
        // réinterprété avec l'indicatif par défaut (Togo) et rejeté comme
        // invalide au lieu d'être relu comme "+33612345678".
        $asAlreadyInternational = self::parse('+'.$digits, null);
        if ($asAlreadyInternational !== '') {
            return $asAlreadyInternational;
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
