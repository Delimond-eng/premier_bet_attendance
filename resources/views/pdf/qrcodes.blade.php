<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR CODES DES STATIONS - SALAMA GROUP</title>
    <style>
        @page {
            margin: 20px;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            color: #111827;
            background-color: #ffffff;
        }

        .container {
            width: 100%;
        }

        .qr-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .qr-cell {
            width: 50%;
            padding: 15px;
            vertical-align: top;
        }

        .card {
            border: 2px solid #1e40af;
            border-radius: 0 !important; /* Force coins carrés */
            overflow: hidden;
            background: #ffffff;
            page-break-inside: avoid;
            text-align: center;
            /* Suppression du shadow pour un look plus industriel et net */
        }

        .card-header {
            background: #1e40af;
            color: #ffffff;
            padding: 15px 10px;
        }

        .company-name {
            font-size: 18px;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            font-family: Arial, sans-serif;
            letter-spacing: 1.5px;
        }

        .kiosk-title {
            font-size: 10px;
            font-weight: 400;
            margin: 2px 0 0;
            opacity: 0.85;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .card-body {
            padding: 20px 15px;
            background: #ffffff;
        }

        .qr-wrapper {
            background: #ffffff;
            padding: 10px;
            display: inline-block;
            border: 1px solid #e2e8f0;
            border-radius: 0 !important; /* Force coins carrés pour le bloc QR */
        }

        .qr-img {
            width: 200px;
            height: 200px;
            display: block;
            border-radius: 0 !important;
        }

        .station-footer {
            padding: 15px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .station-name {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: #1e40af;
            text-transform: uppercase;
        }

        .station-code {
            margin: 4px 0 0;
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
        }

        .cut-line {
            border-top: 2px dashed #cbd5e1;
            margin: 20px 0;
            width: 100%;
            height: 1px;
        }

        .instruction {
            font-size: 9px;
            color: #94a3b8;
            margin-top: 10px;
            font-style: italic;
        }
    </style>
</head>
<body>

    <div class="container">
        <table class="qr-grid">
            @php $count = count($areas); @endphp
            @foreach($areas as $index => $area)
                @if($index % 2 === 0)
                    <tr>
                @endif

                <td class="qr-cell">
                    <div class="card">
                        <div class="card-header">
                            <p class="company-name">SALAMA GROUP LTD</p>
                            <p class="kiosk-title">SMART KIOSK</p>
                        </div>

                        <div class="card-body">
                            <div class="qr-wrapper">
                                <img class="qr-img" src="{{ $area['qrcode'] ?? '' }}" alt="QR Code">
                            </div>
                            <p class="instruction">Scanner pour valider votre présence</p>
                        </div>

                        <div class="station-footer">
                            <p class="station-name">{{ $area['name'] ?? '-' }}</p>
                            <p class="station-code">ID: {{ ($area['code'] ?? 'N/A') }}</p>
                        </div>
                    </div>
                </td>

                @if(($index + 1) % 2 === 0 || ($index + 1) === $count)
                    </tr>
                    @if(($index + 1) !== $count)
                        <tr>
                            <td colspan="2"><div class="cut-line"></div></td>
                        </tr>
                    @endif
                @endif
            @endforeach
        </table>
    </div>

</body>
</html>
