<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Salama notification</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f6f6f6; font-family: Arial, sans-serif;">
    <table align="center" cellpadding="0" cellspacing="0" width="100%" style="padding: 40px 0;">
        <tr>
            <td align="center">
                <table cellpadding="20" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
                    <tr>
                        <td align="center">
                            <!-- <img src="assets/images/mamba-2.png" alt="Logo" width="100" style="margin-bottom: 20px;"> -->
                            <h2 style="color: #333333; margin-bottom: 10px;">{{ $titre }}</h2>
                        </td>
                    </tr>
                    @if ($photo)
                    <tr>
                        <td align="center">
                            <img src="{{ $photo }}" alt="Photo Agent" width="150" height="150" style="border-radius: 8px; object-fit: cover; margin-bottom: 20px;">
                        </td>
                    </tr>
                    @endif

                    <tr>
                        <td style="text-align: center; padding: 0 30px;">
                            <p style="font-size: 16px; color: #555555; margin: 5px 0;"><strong>Agent :</strong> {{ $agent }}</p>
                            <p style="font-size: 16px; color: #555555; margin: 5px 0;"><strong>Site :</strong> {{ $site }}</p>
                            <p style="font-size: 16px; color: #555555; margin: 5px 0;"><strong>Date & Heure :</strong> {{ $datetime }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding-top: 20px;">
                            <p style="font-size: 14px; color: #999999;">Salama plateforme.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
