<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Alerte de présence</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background-color: #ffffff; padding: 20px; border-radius: 10px; border: 1px solid #ddd;">
        <h2 style="color: #c0392b;">Alerte : Présence en dehors du site assigné</h2>

        <p><strong>Agent :</strong> {{ $agent }}</p>
        <p><strong>Site assigné :</strong> {{ $site }}</p>
        <p><strong>Site détecté par GPS :</strong> {{ $site_detecte }}</p>
        <p><strong>Date et heure :</strong> {{ $date }}</p>

        @if ($photo)
            <hr>
            <p><strong>Photo de pointage :</strong></p>
            <img src="{{ $photo }}" alt="Photo présence" style="max-width: 100%; border: 1px solid #ccc; border-radius: 5px;">
        @endif

        <hr>
        <p style="font-size: 13px; color: #888;">Ce message a été généré automatiquement par le système de pointage.</p>
    </div>
</body>
</html>
