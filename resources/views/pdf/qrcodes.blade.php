<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR CODES DES STATIONS - SALAMA GROUP</title>
    <style>
        @page {
            size: A4;
            margin: 12mm;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            color: #111827;
            background: #ffffff;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .qr-grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .qr-cell {
            width: 33.333%;
            padding: 8px;
            vertical-align: top;
        }

        .card {
            border: 2px solid #1e40af;
            border-radius: 0;
            overflow: hidden;
            background: #ffffff;
            text-align: center;
            page-break-inside: avoid;
        }

        .card-header {
            background: #1e40af;
            color: #ffffff;
            padding: 10px 6px;
        }

        .company-name {
            font-size: 13px;
            font-weight: 800;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .kiosk-title {
            font-size: 9px;
            font-weight: 400;
            margin: 3px 0 0;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1.5px;
        }

        .card-body {
            padding: 12px 8px 8px;
            background: #ffffff;
        }

        .qr-wrapper {
            display: inline-block;
            padding: 6px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0;
        }

        .qr-img {
            width: 125px;
            height: 125px;
            display: block;
        }

        .instruction {
            font-size: 8px;
            color: #94a3b8;
            margin: 8px 0 0;
            font-style: italic;
        }

        .station-footer {
            padding: 10px 8px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .station-name {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            color: #1e40af;
            text-transform: uppercase;
            line-height: 1.3;
        }

        .station-code {
            margin: 4px 0 0;
            font-size: 9px;
            color: #64748b;
            font-weight: 600;
        }

        .empty-cell {
            width: 33.333%;
            padding: 8px;
        }
    </style>
</head>
<body>

@foreach(array_chunk($areas, 9) as $page)
    <div class="page">
        <table class="qr-grid">
            @foreach($page as $index => $area)
                @if($index % 3 === 0)
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

                @if(($index + 1) % 3 === 0)
                    </tr>
                @endif
            @endforeach

            @php
                $remaining = count($page) % 3;
            @endphp

            @if($remaining !== 0)
                @for($i = 0; $i < 3 - $remaining; $i++)
                    <td class="empty-cell"></td>
                @endfor
                </tr>
            @endif
        </table>
    </div>
@endforeach

</body>
</html>
