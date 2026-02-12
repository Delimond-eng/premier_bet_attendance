<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR CODES DES ZONES</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            width: 25%;
            padding: 10px;
            vertical-align: top;
        }

        .qr-card {
            width: 100%;
            border-radius: 15px;
            overflow: hidden;
            border: 1px solid #3498db;
            background-color: #fff;
            text-align: center;
            position: relative;
            padding: 0;
        }

        .top-banner {
            background-color: #3498db;
            color: #fff;
            padding: 8px 5px 4px;
            border-top-left-radius: 13.5px;
            border-top-right-radius: 13.5px;
            margin: 0;
        }


        .top-banner .title {
            font-weight: bold;
            font-size: 12px;
            margin-top: 5px;
        }

        .top-banner .subtitle {
            font-size: 8px;
        }

        .qr-body {
            background: #fff;
            padding: 10px;
        }

        .qr-body img {
            width: 100px;
            height: 100px;
        }

        .label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #000000;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <table>
        @foreach($areas as $index => $area)
            @if($index % 4 === 0)
                <tr>
            @endif

            <td>
                <div class="qr-card">
                    <div class="top-banner">
                        <div class="title">SCAN ME</div>
                        <div class="subtitle">Hold the camera to the image</div>
                    </div>
                    <div class="qr-body">
                        <img src="{!! $area['qrcode'] !!}" alt="QR Code">
                        <div class="label">{{ $area["name"] }}</div>
                    </div>
                </div>
            </td>

            @if(($index + 1) % 4 === 0 || $index === count($areas) - 1)
                </tr>
            @endif
        @endforeach
    </table>
</body>
</html>
