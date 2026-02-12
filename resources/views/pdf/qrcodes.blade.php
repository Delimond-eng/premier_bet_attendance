<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR CODES DES STATIONS</title>
    <style>
        @page {
            margin: 16px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
            color: #111827;
        }

        .doc-title {
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 0.4px;
            margin: 0 0 10px;
        }

        .doc-subtitle {
            font-size: 10px;
            color: #6b7280;
            margin: 0 0 14px;
        }

        table.qr-grid {
            width: 100%;
            border-collapse: collapse;
        }

        td.qr-cell {
            width: 25%;
            padding: 10px 8px;
            vertical-align: top;
        }

        .qr-card {
            width: 100%;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            background-color: #ffffff;
            page-break-inside: avoid;
        }

        .qr-header {
            background: #0f766e;
            color: #ffffff;
            padding: 10px 12px 8px;
        }

        .qr-header .title {
            font-weight: bold;
            font-size: 12px;
            letter-spacing: 0.6px;
            margin: 0;
        }

        .qr-header .subtitle {
            font-size: 9px;
            opacity: 0.92;
            margin: 2px 0 0;
        }

        .qr-body {
            background: #fff;
            padding: 14px 12px 10px;
            text-align: center;
        }

        .qr-img {
            width: 110px;
            height: 110px;
            display: block;
            margin: 0 auto;
        }

        .qr-footer {
            padding: 10px 12px 12px;
            border-top: 1px solid #f3f4f6;
            text-align: center;
        }

        .label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #111827;
            margin: 0;
            line-height: 1.2;
            word-break: break-word;
        }

        .slogan {
            font-size: 9px;
            color: #6b7280;
            margin: 4px 0 0;
        }
    </style>
</head>
<body>
    <p class="doc-title">QR codes des stations</p>
    <p class="doc-subtitle">Scan pour pointer sur site</p>

    <table class="qr-grid">
        @foreach($areas as $index => $area)
            @if($index % 4 === 0)
                <tr>
            @endif

            <td class="qr-cell">
                <div class="qr-card">
                    <div class="qr-header">
                        <p class="title">SCAN</p>
                        <p class="subtitle">Approche la camera du code</p>
                    </div>
                    <div class="qr-body">
                        <img class="qr-img" src="{{ $area['qrcode'] }}" alt="QR Code">
                    </div>
                    <div class="qr-footer">
                        <p class="label">{{ $area["name"] }}</p>
                        <p class="slogan">Salama attendance</p>
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
