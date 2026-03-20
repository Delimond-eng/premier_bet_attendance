@extends('pdf.exports._base')

@section('body')
    <style>
        @page {
            margin: 0.5cm;
        }

        .detailed-report-wrapper {
            width: 100%;
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #1e293b;
        }

        .report-header-box {
            background-color: #f8fafc;
            border-left: 5px solid #1a592e;
            padding: 20px;
            margin-bottom: 25px;
            border-radius: 0 4px 4px 0;
        }

        .report-header-box h1 {
            margin: 0;
            font-size: 22px;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Table Design */
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #94a3b8;
        }

        .attendance-table th {
            background-color: #1a592e;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            padding: 10px 2px;
            border: 1px solid #144624;
        }

        /* COLONNE AGENT - ULTRA LARGE ET VISIBLE */
        .col-agent-identity {
            width: 450px !important; /* Largeur massive pour le nom */
            text-align: left !important;
            padding: 12px 15px !important;
            background-color: #f8fafc;
            border-right: 3px solid #cbd5e1 !important;
        }

        .agent-full-name {
            font-size: 14px; /* Nom très grand */
            font-weight: 900;
            color: #0f172a;
            display: block;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .agent-meta-data {
            font-size: 10px;
            color: #475569;
            display: block;
            font-weight: normal;
        }

        /* Colonnes JOURS - Très compactes */
        .col-day-cell {
            width: 12px !important;
            font-size: 10px;
            font-weight: bold;
            border: 1px solid #e2e8f0;
            text-align: center;
            padding: 8px 0;
        }

        /* Colonnes STATISTIQUES */
        .col-stat-summary {
            width: 35px;
            font-weight: 800;
            font-size: 10px;
            background-color: #f1f5f9;
            border-left: 1px solid #94a3b8;
            text-align: center;
            color: #1e293b;
        }

        /* Status Colors */
        .code-1   { background-color: #dcfce7; color: #166534; }
        .code-1-R { background-color: #e0f2fe; color: #075985; }
        .code-A   { background-color: #fee2e2; color: #991b1b; }
        .code-OFF { background-color: #f8fafc; color: #94a3b8; font-size: 7px; }
        .code-C   { background-color: #eff6ff; color: #1e40af; }
        .code-AS  { background-color: #faf5ff; color: #6b21a8; }
        .code-fut { color: #cbd5e1; background-color: #ffffff; }

        tbody tr:nth-child(even) { background-color: #ffffff; }
        tbody tr:nth-child(odd) { background-color: #fcfcfc; }

        .footer-legend {
            margin-top: 30px;
            padding: 20px;
            background-color: #f1f5f9;
            border-radius: 8px;
        }

        .legend-item {
            display: inline-block;
            margin-right: 25px;
            font-size: 11px;
            color: #334155;
        }

        .swatch {
            display: inline-block;
            width: 14px;
            height: 14px;
            margin-right: 8px;
            vertical-align: middle;
            border-radius: 3px;
            border: 1px solid #cbd5e1;
        }
    </style>

    <div class="detailed-report-wrapper">
        <div class="report-header-box">
            <h1>Registre Mensuel des Présences</h1>
            <p style="margin-top: 8px; font-size: 12px; color: #475569;">
                Période : <span style="color: #0f172a; font-weight: bold;">{{ \Carbon\Carbon::createFromDate($year, $month, 1)->translatedFormat('F Y') }}</span> |
                Station : <span style="color: #0f172a; font-weight: bold;">{{ $station->name ?? 'Toutes les stations' }}</span>
            </p>
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="col-agent-identity">Identité de l'Agent</th>
                    @for($d = 1; $d <= $daysInMonth; $d++)
                        <th class="col-day-cell">{{ sprintf('%02d', $d) }}</th>
                    @endfor
                    <th class="col-stat-summary">TOT</th>
                    <th class="col-stat-summary">PRS</th>
                    <th class="col-stat-summary">ABS</th>
                    <th class="col-stat-summary">RET</th>
                    <th class="col-stat-summary">CNG</th>
                    <th class="col-stat-summary">HS</th>
                    <th class="col-stat-summary">OFF</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $r)
                    <tr>
                        <td class="col-agent-identity">
                            <span class="agent-full-name">{{ $r['agent']['fullname'] ?? '-' }}</span>
                            <span class="agent-meta-data">
                                <strong>{{ $r['agent']['matricule'] ?? 'N/A' }}</strong> — {{ $r['agent']['station_name'] ?? '-' }}
                            </span>
                        </td>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php
                                $code = $r['days'][sprintf('%02d', $d)] ?? '--';
                                $class = 'code-' . str_replace('1-R', '1-R', $code);
                                if ($code == '--') $class = 'code-fut';
                            @endphp
                            <td class="col-day-cell {{ $class }}">
                                {{ $code }}
                            </td>
                        @endfor
                        <td class="col-stat-summary">{{ $r['total_count'] }}</td>
                        <td class="col-stat-summary" style="color: #166534;">{{ $r['total_presences'] }}</td>
                        <td class="col-stat-summary" style="color: #991b1b;">{{ $r['total_absences'] }}</td>
                        <td class="col-stat-summary" style="color: #075985;">{{ $r['total_retards'] }}</td>
                        <td class="col-stat-summary">{{ $r['total_conges'] }}</td>
                        <td class="col-stat-summary" style="background-color: #fffbeb;">{{ $r['overtime_display'] }}</td>
                        <td class="col-stat-summary">{{ $r['total_off'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer-legend">
            <div style="font-weight: bold; margin-bottom: 10px; font-size: 12px;">Légende :</div>
            <div class="legend-item"><span class="swatch code-1"></span> <strong>1</strong> : Présent</div>
            <div class="legend-item"><span class="swatch code-1-R"></span> <strong>1-R</strong> : Retard</div>
            <div class="legend-item"><span class="swatch code-A"></span> <strong>A</strong> : Absent / Justifié</div>
            <div class="legend-item"><span class="swatch code-OFF"></span> <strong>OFF</strong> : Repos</div>
            <div class="legend-item"><span class="swatch code-C"></span> <strong>C</strong> : Congé</div>
            <div class="legend-item"><span class="swatch code-AS"></span> <strong>AS</strong> : Autorisation</div>
        </div>
    </div>
@endsection
