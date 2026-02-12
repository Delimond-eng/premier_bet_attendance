<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de Patrouilles</title>
    <style>
        @page {
            margin: 20mm 15mm;
        }

        body {
            font-family: 'Segoe UI', 'DejaVu Sans', sans-serif;
            font-size: 13px;
            color: #111827;
            margin: 0;
            background-color: #f9fafb;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0;
        }

        .header {
            background: #1e3fab;
            color: white;
            padding: 1rem 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .header-title {
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .summary-stats {
            background-color: #e0e7ff;
            border-left: 5px solid #1e3fab;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            background: white;
            padding: 1rem;
            border-radius: 6px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #1e3fab;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: bold;
            color: #1e3fab;
        }

        .patrol {
            background: white;
            margin-bottom: 2rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .patrol-header {
            background: #1e3fab;
            color: white;
            padding: 1rem;
            font-weight: bold;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .patrol-details {
            padding: 1rem;
            background-color: #f3f4f6;
            border-bottom: 1px solid #e5e7eb;
        }

        .patrol-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            display: flex;
            gap: 0.4rem;
            align-items: center;
        }

        .detail-label {
            font-weight: 600;
            color: #374151;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .table th {
            background-color: #1e3fab;
            color: white;
            padding: 0.7rem;
            text-align: left;
            font-size: 12px;
            border-bottom: 2px solid #c7d2fe;
        }

        .table td {
            padding: 0.7rem;
            border-bottom: 1px solid #e5e7eb;
            font-size: 12px;
        }

        .table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .status-success {
            color: #10b981;
            font-weight: 600;
        }

        .status-warning {
            color: #f59e0b;
            font-weight: 600;
        }

        .efficiency-high {
            color: #10b981;
            font-weight: bold;
        }

        .efficiency-medium {
            color: #f59e0b;
            font-weight: bold;
        }

        .efficiency-low {
            color: #ef4444;
            font-weight: bold;
        }

        footer {
            text-align: center;
            padding: 1.5rem 0;
            color: #6b7280;
            font-size: 0.875rem;
            border-top: 1px solid #d1d5db;
        }

        @media print {
            body {
                background: white;
                -webkit-print-color-adjust: exact;
            }

            .container {
                padding: 0;
                max-width: none;
            }

            .patrol {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            footer {
                position: fixed;
                bottom: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container header-content">
            <h1 class="header-title">Rapport Général de Patrouilles</h1>
            <div>{{ now()->format('d/m/Y') }}</div>
        </div>
    </header>

    <div class="container">
        @foreach($patrols as $patrol)
            <div class="patrol">
                <div class="patrol-header">
                    <div>Patrouille #{{ $patrol->id }} – Agent: {{ $patrol->agent->fullname }}</div>
                    <div>Site: {{ $patrol->site->name }}</div>
                </div>

                <div class="patrol-details">
                    <div class="patrol-grid">
                        <div class="detail-item"><span class="detail-label">Début:</span> {{ $patrol->started_at }}</div>
                        <div class="detail-item"><span class="detail-label">Fin:</span> {{ $patrol->ended_at }}</div>
                        <div class="detail-item"><span class="detail-label">Durée réelle:</span> {{ $patrol->duration_minutes }} min</div>
                        <div class="detail-item"><span class="detail-label">Durée estimée:</span> {{ $patrol->estimated_duration_minutes }} min</div>
                        <div class="detail-item"><span class="detail-label">Efficacité:</span>
                            <span class="{{ $patrol->efficiency_score >= 90 ? 'efficiency-high' : ($patrol->efficiency_score >= 70 ? 'efficiency-medium' : 'efficiency-low') }}">
                                {{ $patrol->efficiency_score }}%
                            </span>
                        </div>
                        <div class="detail-item"><span class="detail-label">Zones scannées:</span>
                            {{ $patrol->zones_scanned }} / {{ $patrol->zones_expected }} ({{ $patrol->coverage_rate }}%)
                        </div>
                    </div>
                </div>

                <table class="table">
                    <thead>
                        <tr>
                            <th>Zone</th>
                            <th>Heure</th>
                            <th>Distance</th>
                            <th>Durée depuis la précédente</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($patrol->scans_stats as $scan)
                            <tr>
                                <td>{{ $scan['area'] }}</td>
                                <td>{{ $scan['time'] }}</td>
                                <td>{{ $scan['distance_meters'] }} m</td>
                                <td>{{ $scan['duration_since_previous_seconds'] }} s</td>
                                <td>
                                    @if($scan['distance_meters'] <= 1)
                                        <span class="status-success">Succès</span>
                                    @else
                                        <span class="status-warning">Éloigné</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach

        <footer>
            <div>Page {{ $page ?? 1 }}</div>
            <div>© {{ date('Y') }} Salama Plateforme - Tous droits réservés</div>
        </footer>
    </div>
</body>
</html>
