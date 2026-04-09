<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR CODES DES STATIONS - SALAMA GROUP</title>
    <style>
        @page {
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            color: #111827;
            background: #ffffff;
        }

        .qr-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .qr-cell {
            padding: 5px;
            vertical-align: top;
        }

        tr {
            page-break-inside: avoid;
        }

        .card {
            border: 2px solid #1e40af;
            border-radius: 0;
            overflow: hidden;
            background: #ffffff;
            text-align: center;
            margin-bottom: 5px;
        }

        .card-header {
            background: #1e40af;
            color: #ffffff;
            padding: 8px 4px;
        }

        .company-name {
            font-size: 11px;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .kiosk-title {
            font-size: 8px;
            font-weight: 400;
            margin: 2px 0 0;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1.2px;
        }

        .card-body {
            padding: 10px 5px 5px;
            background: #ffffff;
        }

        .qr-wrapper {
            display: inline-block;
            padding: 4px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
        }

        .qr-img {
            width: 130px;
            height: 130px;
            display: block;
        }

        .instruction {
            font-size: 7px;
            color: #94a3b8;
            margin: 5px 0 0;
            font-style: italic;
        }

        .station-footer {
            padding: 8px 5px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .station-name {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            color: #1e40af;
            text-transform: uppercase;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
        }

        .station-code {
            margin: 2px 0 0;
            font-size: 8px;
            color: #64748b;
            font-weight: 600;
        }

        .empty-cell {
            padding: 5px;
        }
    </style>
</head>
<body>

@php
    $cols = (int)($cols ?? 3);
    $width = 100 / $cols;
@endphp

<table class="qr-grid">
    @foreach($areas as $index => $area)
        @if($index % $cols === 0)
            <tr>
        @endif

        <td class="qr-cell" style="width: {{ $width }}%;">
            <div class="card">
                <div class="card-header">
                    <p class="company-name">SALAMA GROUP LTD</p>
                    <p class="kiosk-title">SMART KIOSK</p>
                </div>

                <div class="card-body">
                    <div class="qr-wrapper">
                        <img
                            class="qr-img"
                            src="{{ $area['qrcode'] ?? '' }}"
                            alt="QR Code"
                        >
                    </div>
                    <p class="instruction">Scanner pour valider votre présence</p>
                </div>

                <div class="station-footer">
                    <p class="station-name">{{ $area['name'] ?? '-' }}</p>
                    <p class="station-code">ID: {{ $area['code'] ?? 'N/A' }}</p>
                </div>
            </div>
        </td>

        @if(($index + 1) % $cols === 0)
            </tr>
        @endif
    @endforeach

    @php
        $remaining = count($areas) % $cols;
    @endphp

    @if($remaining !== 0)
        @for($i = 0; $i < $cols - $remaining; $i++)
            <td class="empty-cell" style="width: {{ $width }}%;"></td>
        @endfor
        </tr>
    @endif
</table>

</body>
</html>
