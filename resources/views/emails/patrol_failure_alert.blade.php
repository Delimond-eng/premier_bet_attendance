<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Alerte Patrouille</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f6f8fa;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 680px;
            margin: 30px auto;
            background: #ffffff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .header {
            background-color: #dc3545;
            color: white;
            padding: 20px;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 22px;
        }

        .content {
            padding: 20px 30px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section h2 {
            font-size: 18px;
            margin-bottom: 10px;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
            color: #333;
        }

        .section p {
            margin: 5px 0;
        }

        .zone-list {
            list-style: none;
            padding: 0;
            margin: 10px 0;
        }

        .zone-list li {
            padding: 5px 10px;
            background: #f1f1f1;
            border-radius: 4px;
            margin-bottom: 5px;
        }

        .zone-list.missing li {
            background-color: #ffe1e1;
            color: #a60000;
            font-weight: bold;
        }

        .footer {
            text-align: center;
            font-size: 13px;
            color: #777;
            padding: 15px 20px;
            background: #f9f9f9;
            border-top: 1px solid #ddd;
        }

        .photo-agent {
            text-align: center;
            margin-top: 15px;
        }

        .photo-agent img {
            border-radius: 8px;
            border: 1px solid #ccc;
            max-width: 150px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Alerte de Patrouille Non Respectée</h1>
    </div>
    <div class="content">
        <div class="section">
            <h2>Détails du planning</h2>
            <p><strong>Libellé :</strong> {{ $schedule->libelle }}</p>
            <p><strong>Site :</strong> {{ $site->name }}</p>
            <p><strong>Date :</strong> {{ $schedule->date->format("d/m/Y") }}</p>
            <p><strong>Heure :</strong> {{ $schedule->start_time }} → {{ $schedule->end_time ?? '-' }}</p>
            <p><strong>Vérification à :</strong> {{ $now->format('Y-m-d H:i') }}</p>
        </div>

        <div class="section">
            <h2>Informations sur l'agent</h2>
            <p><strong>Nom :</strong> {{ $agentName }}</p>

            @if($photo)
                <div class="photo-agent">
                    <img src="{{ asset('storage/' . $photo) }}" alt="Photo de l'agent">
                </div>
            @endif
        </div>

        @if(count($missingAreas) > 0)
            <div class="section">
                <h2 style="color: #a60000;">Zones manquées ({{ count($missingAreas) }})</h2>
                <ul class="zone-list missing">
                    @foreach($missingAreas as $area)
                        <li>{{ $area }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="section">
            <p><strong>Veuillez vérifier cette patrouille et prendre les mesures nécessaires.</strong></p>
        </div>
    </div>
    <div class="footer">
        Cet email a été généré automatiquement par le système de contrôle des patrouilles.
    </div>
</div>
</body>
</html>
