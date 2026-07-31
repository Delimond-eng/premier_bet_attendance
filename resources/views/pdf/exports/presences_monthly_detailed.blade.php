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
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }

        .report-header-box h1 {
            margin: 0;
            font-size: 20px;
            color: #0f172a;
            text-transform: uppercase;
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
            font-size: 8px;
            font-weight: bold;
            padding: 8px 1px;
            border: 1px solid #144624;
        }

        /* COLONNE AGENT */
        .col-agent-identity {
            width: 200px !important;
            text-align: left !important;
            padding: 8px 10px !important;
            background-color: #f8fafc;
            border-right: 2px solid #cbd5e1 !important;
        }

        .agent-full-name {
            font-size: 10px;
            font-weight: 900;
            color: #0f172a;
            display: block;
            text-transform: uppercase;
        }

        .agent-meta-data {
            font-size: 7px;
            color: #475569;
            display: block;
        }

        /* Colonnes JOURS */
        .col-day-cell {
            font-size: 7px;
            font-weight: bold;
            border: 1px solid #e2e8f0;
            text-align: center;
            padding: 4px 0;
        }

        .day-header-multi {
            line-height: 1;
            padding: 4px 0 !important;
        }

        .day-header-multi .day-num {
            display: block;
            font-size: 10px;
            font-weight: 900;
        }

        .day-header-multi .month-num {
            display: block;
            font-size: 6px;
            opacity: 0.8;
            font-weight: normal;
        }

        /* Colonnes STATISTIQUES */
        .col-stat-summary {
            width: 22px;
            font-weight: 800;
            font-size: 7px;
            background-color: #f1f5f9;
            border-left: 1px solid #94a3b8;
            text-align: center;
        }

        .col-stat-summary-large {
            width: 35px;
            font-weight: 800;
            font-size: 7px;
            background-color: #f1f5f9;
            border-left: 1px solid #94a3b8;
            text-align: center;
        }

        /* Status Colors */
        .code-1   { background-color: #dcfce7; color: #166534; }
        .code-1-R { background-color: #e0f2fe; color: #075985; }
        .code-A   { background-color: #fee2e2; color: #991b1b; }
        .code-AN  { background-color: #fee2e2; color: #991b1b; border: 1px solid #991b1b !important; }
        .code-OFF { background-color: #f1f5f9; color: #94a3b8; }
        .code-CONGE { background-color: #eff6ff; color: #1e40af; }
        .code-AS  { background-color: #faf5ff; color: #6b21a8; }
        .code-M   { background-color: #fff9c4; color: #856404; }
        .code-fut { color: #cbd5e1; background-color: #ffffff; }

        tbody tr:nth-child(even) { background-color: #ffffff; }
        tbody tr:nth-child(odd) { background-color: #fcfcfc; }

        .footer-legend {
            margin-top: 15px;
            padding: 10px;
            background-color: #f1f5f9;
        }

        .legend-item {
            display: inline-block;
            margin-right: 15px;
            font-size: 8px;
            color: #334155;
        }

        .swatch {
            display: inline-block;
            width: 8px;
            height: 8px;
            margin-right: 4px;
            vertical-align: middle;
            border: 1px solid #cbd5e1;
        }
    </style>

    <div class="detailed-report-wrapper">
        <div class="report-header-box">
            <h1>Registre des Présences</h1>
            <p style="margin-top: 5px; font-size: 10px; color: #475569;">
                Période : <span style="color: #0f172a; font-weight: bold;">{{ ($from ?? null) && ($to ?? null) ? $from . ' au ' . $to : \Carbon\Carbon::createFromDate($year ?? date('Y'), $month ?? date('m'), 1)->translatedFormat('F Y') }}</span> |
                Station : <span style="color: #0f172a; font-weight: bold;">{{ $station->name ?? 'Toutes les stations' }}</span>
            </p>
        </div>

        <table class="attendance-table">
            <thead>
                <tr>
                    <th class="col-agent-identity">Agent</th>
                    <th class="col-stat-summary" title="Fonction">Fonction</th>
                    @foreach(($days ?? []) as $day)
                        @php
                            $isMulti = strpos($day, '/') !== false;
                            $dayNum = $isMulti ? explode('/', $day)[0] : $day;
                            $monthNum = $isMulti ? explode('/', $day)[1] : '';
                        @endphp
                        <th class="col-day-cell {{ $isMulti ? 'day-header-multi' : '' }}">
                            @if($isMulti)
                                <span class="day-num">{{ $dayNum }}</span>
                                <span class="month-num">{{ $monthNum }}</span>
                            @else
                                {{ $dayNum }}
                            @endif
                        </th>
                    @endforeach
                    <th class="col-stat-summary" title="Total jours">TOT</th>
                    <th class="col-stat-summary" title="Présences">PRS</th>
                    <th class="col-stat-summary" title="Absences">ABS</th>
                    <th class="col-stat-summary" title="Oubli de sortie">AN</th>
                    <th class="col-stat-summary" title="Retards (nombre)">RET</th>
                    <th class="col-stat-summary-large" title="Retard Cumulé">R.CUM</th>
                    <th class="col-stat-summary" title="Congés">CNG</th>
                    <th class="col-stat-summary-large" title="Heures Supplémentaires">H.SUP</th>
                    <th class="col-stat-summary" title="Repos">OFF</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($rows ?? []) as $r)
                    <tr>
                        <td class="col-agent-identity">
                            <span class="agent-full-name">{{ $r['agent']['fullname'] ?? '-' }}</span>
                            <span class="agent-meta-data">
                                {{ $r['agent']['matricule'] ?? 'N/A' }} — {{ $r['agent']['station_name'] ?? '-' }}
                            </span>
                        </td>
                        <td class="col-stat-summary" style="background-color: #f8fafc; color: #0f172a;">{{ $r['agent']['fonction'] ?? '-' }}</td>
                        @foreach(($days ?? []) as $day)
                            @php
                                $code = $r['days'][$day] ?? '--';
                                $class = 'code-' . $code;
                                if ($code == '--') $class = 'code-fut';
                            @endphp
                            <td class="col-day-cell {{ $class }}">
                                {{ $code }}
                            </td>
                        @endforeach
                        <td class="col-stat-summary">{{ $r['total_count'] ?? 0 }}</td>
                        <td class="col-stat-summary" style="color: #166534;">{{ $r['total_presences'] ?? 0 }}</td>
                        <td class="col-stat-summary" style="color: #991b1b;">{{ $r['total_absences'] ?? 0 }}</td>
                        <td class="col-stat-summary" style="color: #991b1b;">{{ $r['total_an'] ?? 0 }}</td>
                        <td class="col-stat-summary" style="color: #075985;">{{ $r['total_retards'] ?? 0 }}</td>
                        <td class="col-stat-summary-large" style="color: #991b1b;">{{ $r['late_display'] ?? '0h' }}</td>
                        <td class="col-stat-summary">{{ $r['total_conges'] ?? 0 }}</td>
                        <td class="col-stat-summary-large" style="background-color: #fffbeb;">{{ $r['overtime_display'] ?? '0h' }}</td>
                        <td class="col-stat-summary">{{ $r['total_off'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="footer-legend">
            <div class="legend-item"><span class="swatch code-1"></span> <strong>1</strong> : Présent</div>
            <div class="legend-item"><span class="swatch code-1-R"></span> <strong>1-R</strong> : Retard</div>
            <div class="legend-item"><span class="swatch code-A"></span> <strong>A</strong> : Absent / Justifié</div>
            <div class="legend-item"><span class="swatch code-AN"></span> <strong>AN</strong> : Entrée sans sortie</div>
            <div class="legend-item"><span class="swatch code-OFF"></span> <strong>OFF</strong> : Repos</div>
            <div class="legend-item"><span class="swatch code-CONGE"></span> <strong>C</strong> : Congé</div>
            <div class="legend-item"><span class="swatch code-AS"></span> <strong>AS</strong> : Autorisation</div>
            <div class="legend-item"><span class="swatch code-M"></span> <strong>M</strong> : Maladie</div>
            <div class="legend-item"><strong>R.CUM</strong> : Retard Cumulé | <strong>H.SUP</strong> : Heures Supplémentaires</div>
        </div>
    </div>
@endsection
