<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Code de vérification TELU BAOBAB</title>
</head>
<body style="margin:0; padding:0; background-color:#F4F6FB; font-family: Arial, Helvetica, sans-serif; color:#111827;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#F4F6FB; padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:420px; background-color:#FFFFFF; border-radius:12px; padding:32px;">
                    <tr>
                        <td style="text-align:center; padding-bottom:16px;">
                            <strong style="font-size:18px;">TELU BAOBAB</strong>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center; padding-bottom:8px; color:#4B5563; font-size:14px;">
                            Votre code de vérification est :
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center; padding:8px 0 24px;">
                            <span style="display:inline-block; font-size:32px; font-weight:bold; letter-spacing:8px; color:#111827;">{{ $code }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="text-align:center; color:#6B7280; font-size:13px;">
                            Il expire dans {{ $ttlMinutes }} minutes. Ne le partagez avec personne.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
