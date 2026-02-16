<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>QR CODES DES STATIONS</title>
    <style>
        @page {
            margin: 14px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            margin: 0;
            color: #111827;
        }

        .doc-title {
            font-size: 14px;
            font-weight: 700;
            margin: 0 0 4px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .doc-subtitle {
            font-size: 10px;
            margin: 0 0 12px;
            color: #4b5563;
        }

        table.grid {
            width: 100%;
            border-collapse: collapse;
        }

        td.grid-cell {
            width: 50%;
            vertical-align: top;
            padding: 7px;
        }

        .card {
            border: 1px solid #d1d5db;
            page-break-inside: avoid;
        }

        .card-header {
            background: #0f766e;
            color: #ffffff;
            padding: 8px 10px;
        }

        .station-name {
            margin: 0;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.2;
        }

        .station-code {
            margin: 2px 0 0;
            font-size: 9px;
            opacity: 0.95;
        }

        .card-body {
            padding: 10px;
        }

        table.card-layout {
            width: 100%;
            border-collapse: collapse;
        }

        td.qr-col {
            width: 33%;
            text-align: center;
            vertical-align: top;
            border-right: 1px dashed #d1d5db;
            padding-right: 8px;
        }

        td.horaires-col {
            width: 67%;
            vertical-align: top;
            padding-left: 10px;
        }

        .qr-img {
            width: 130px;
            height: 130px;
            display: block;
            margin: 0 auto 6px;
        }

        .qr-label {
            font-size: 9px;
            color: #374151;
            margin: 0;
            line-height: 1.2;
        }

        .horaires-title {
            margin: 0 0 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: #0f766e;
        }

        table.horaires-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        table.horaires-table th {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            padding: 4px 5px;
            text-align: left;
            font-weight: 700;
        }

        table.horaires-table td {
            border: 1px solid #e5e7eb;
            padding: 4px 5px;
            line-height: 1.25;
        }

        .empty {
            margin: 0;
            font-size: 9px;
            color: #6b7280;
            font-style: italic;
        }

        .card-footer {
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            padding: 6px 10px;
            font-size: 8px;
            color: #4b5563;
        }
    </style>
</head>
<body>
    <p class="doc-title">Qr codes stations</p>
    <p class="doc-subtitle">Affichage mural pour pointage. Les horaires affiches sont indicatifs.</p>

    <table class="grid">
        @foreach($areas as $index => $area)
            @if($index % 2 === 0)
                <tr>
            @endif

            <td class="grid-cell">
                <div class="card">
                    <div class="card-header">
                        <p class="station-name">{{ $area['name'] ?? '-' }}</p>
                        <p class="station-code">Code station: {{ ($area['code'] ?? null) ?: 'N/A' }}</p>
                    </div>

                    <div class="card-body">
                        <table class="card-layout">
                            <tr>
                                <td class="qr-col">
                                    <img class="qr-img" src="{{ $area['qrcode'] ?? '' }}" alt="QR Code">
                                    <p class="qr-label">Scanner pour pointer a cette station</p>
                                </td>
                                <td class="horaires-col">
                                    <p class="horaires-title">Horaires de la station</p>
                                    @if(!empty($area['horaires']) && count($area['horaires']) > 0)
                                        <table class="horaires-table">
                                            <thead>
                                            <tr>
                                                <th>Horaire</th>
                                                <th>Debut</th>
                                                <th>Controle</th>
                                                <th>Fin</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @foreach($area['horaires'] as $horaire)
                                                <tr>
                                                    <td>{{ ($horaire['libelle'] ?? '') !== '' ? $horaire['libelle'] : '-' }}</td>
                                                    <td>{{ $horaire['started_at'] ?? '--:--' }}</td>
                                                    <td>{{ $horaire['mid_check'] ?? '--:--' }}</td>
                                                    <td>{{ $horaire['ended_at'] ?? '--:--' }}</td>
                                                </tr>
                                            @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <p class="empty">Aucun horaire configure pour cette station.</p>
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </div>

                    <div class="card-footer">
                        Document indicatif pour affichage au mur - Salama Attendance
                    </div>
                </div>
            </td>

            @if(($index + 1) % 2 === 0 || $index === count($areas) - 1)
                </tr>
            @endif
        @endforeach
    </table>
</body>
</html>
