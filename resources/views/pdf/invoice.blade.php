<!DOCTYPE html>
<html lang="fr">
<head>
    <title>SITE AREAS QRCODES BY Salama</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-size: 12px;
            font-family: 'Arial', sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            padding: 10px;
            text-align: center;
            vertical-align: middle;
        }

        .qr-container {
            width: 150px;
            border: 2px solid #3498db;
            /* Bordure bleue */
            padding: 10px;
            border-radius: 10px;
            /* Coins arrondis */
            position: relative;
            text-align: center;
            background-color: #f9f9f9;
        }

        .qr-container img {
            width: 100px;
            height: 100px;
        }

        .label {
            font-size: 8px;
            color: #3498db;
            margin-top: 5px;
            font-weight: bold;
        }

        .header {
            font-size: 14px;
            font-weight: bold;
            color: #000;
            margin-bottom: 5px;
        }

        .footer {
            font-size: 8px;
            color: #888;
            margin-top: 5px;
        }

    </style>
</head>
<body>
    <table>
        @foreach($areas as $index => $area)
        @if($index % 3 === 0)
        <tr>
            @endif

            <td>
                <div class="qr-container">
                    <div class="header">SCANNEZ ICI</div> <!-- Titre au-dessus du QR code -->
                    <img src="{!! $area['qrcode'] !!}" alt="QR Code">
                    <div class="label" style="text-transform: uppercase;">{{ $area["libelle"] }}</div> <!-- Libellé en dessous du QR code -->
                </div>
            </td>

            @if(($index + 1) % 3 === 0 || $index === count($areas) - 1)
        </tr>
        @endif
        @endforeach
    </table>
</body>
</html>
