<?php

namespace App\Support;

/**
 * Types de pièce d'identité acceptés pour le KYC. Au Togo, la CNI n'est pas
 * universelle : la carte d'électeur (CENI) sert très couramment de pièce
 * d'identité de facto, et la carte nationale biométrique (programme récent)
 * coexiste avec l'ancienne CNI — le document uploadé n'est donc pas toujours
 * une "carte d'identité" au sens strict, d'où ce type explicite à côté de
 * l'URL du document.
 */
final class IdDocumentType
{
    public const CNI = 'cni';

    public const CARTE_ELECTEUR = 'carte_electeur';

    public const CARTE_BIOMETRIQUE = 'carte_biometrique';

    public const PASSEPORT = 'passeport';

    /**
     * @var list<string>
     */
    public const OPTIONS = [
        self::CNI,
        self::CARTE_ELECTEUR,
        self::CARTE_BIOMETRIQUE,
        self::PASSEPORT,
    ];
}
