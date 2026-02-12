<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ronde 011 Confirmée</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 30px auto;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .header {
            background-color: #0e76a8;
            color: #fff;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 25px;
        }
        .content h2 {
            margin-top: 0;
            color: #0e76a8;
        }
        .info {
            margin: 15px 0;
            line-height: 1.6;
        }
        .info strong {
            color: #444;
        }
        .photo {
            text-align: center;
            margin-top: 20px;
        }
        .photo img {
            max-width: 100%;
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .footer {
            font-size: 13px;
            text-align: center;
            color: #999;
            padding: 15px;
        }
        .btn-map {
            display: inline-block;
            background-color: #0e76a8;
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="header">
            <h1>Ronde 011 Confirmée</h1>
        </div>

        <div class="content">
            <h2>Détails de la ronde</h2>
            <div class="info">
                <p><strong>Agent :</strong> {{ $agent->matricule }} - {{ $agent->fullname }}</p>
                <p><strong>Site :</strong> {{ $site->code }} - {{ $site->name }}</p>
                <p><strong>Date/Heure :</strong> {{ $now->format('d/m/Y H:i') }}</p>
                <p><strong>Distance :</strong> {{ $distance }}</p>
                @if($comment)
                    <p><strong>Commentaire :</strong> {{ $comment }}</p>
                @endif
            </div>

            @if($photo)
                <div class="photo">
                    <h3>Photo de l'agent</h3>
                    <img src="{{ $photo }}" alt="Photo de la ronde">
                </div>
            @endif
        </div>
        <div class="footer">
            Ceci est un email automatique généré par le système de contrôle des rondes.
        </div>
    </div>

</body>
</html>
