@extends('pdf.exports._base')

@php
    $metaLines = [
        'Mois: ' . sprintf('%02d', (int) ($month ?? 0)) . '/' . (string) ($year ?? ''),
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Format: Details',
        'Lignes: ' . (is_array($rows ?? null) ? count($rows) : 0),
    ];
@endphp

@section('body')
    <style>
        table { table-layout: auto; }
        th, td { padding: 3px 2px; font-size: 8px; text-align: center; }
        .col-matricule { min-width: 56px; }
        .col-agent { min-width: 140px; text-align: left; }
        .col-station { min-width: 90px; text-align: left; }
        .cell-presence { background: #dcfce7; }
        .cell-retard { background: #0ea5e9; color: #fff; }
        .cell-absence { background: #ef4444; color: #fff; }
        .cell-off { background: #64748b; color: #fff; }
        .cell-conge { background: #2563eb; color: #fff; }
        .cell-autorisation { background: #111827; color: #fff; }
        .cell-other { background: #fef3c7; color: #111827; }
        .cell-future { background: #f8fafc; color: #64748b; }
    </style>

    <table>
        <thead>
        <tr>
            <th class="col-matricule">Matricule</th>
            <th class="col-agent">Nom complet agent</th>
            <th class="col-station">Fonction</th>
            <th class="col-station">Station</th>
            @foreach(($days ?? []) as $day)
                <th>{{ $day }}</th>
            @endforeach
            <th>Total</th>
            <th>Tot presences</th>
            <th>Tot absences</th>
            <th>Tot retard</th>
            <th>Tot autorisation</th>
            <th>Tot conge</th>
            <th>Tot OFF</th>
            <th>Tot autres</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($rows ?? []) as $r)
            @php($a = $r['agent'] ?? [])
            <tr>
                <td class="col-matricule">{{ $a['matricule'] ?? '' }}</td>
                <td class="col-agent">{{ $a['fullname'] ?? '-' }}</td>
                <td class="col-station">{{ $a['fonction'] ?? '-' }}</td>
                <td class="col-station">{{ $a['station_name'] ?? '-' }}</td>
                @foreach(($days ?? []) as $day)
                    @php
                        $bucket = $r['day_buckets'][$day] ?? null;
                        $class = match ($bucket) {
                            'presence' => 'cell-presence',
                            'retard' => 'cell-retard',
                            'absence' => 'cell-absence',
                            'off' => 'cell-off',
                            'conge' => 'cell-conge',
                            'autorisation' => 'cell-autorisation',
                            'other' => 'cell-other',
                            null => 'cell-future',
                            default => 'cell-other',
                        };
                    @endphp
                    <td class="{{ $class }}">{{ $r['day_codes'][$day] ?? '--' }}</td>
                @endforeach
                <td>{{ $r['total_count'] ?? 0 }}</td>
                <td>{{ $r['total_presences'] ?? 0 }}</td>
                <td>{{ $r['total_absences'] ?? 0 }}</td>
                <td>{{ $r['total_retards'] ?? 0 }}</td>
                <td>{{ $r['total_autorisations'] ?? 0 }}</td>
                <td>{{ $r['total_conges'] ?? 0 }}</td>
                <td>{{ $r['total_off'] ?? 0 }}</td>
                <td>{{ $r['total_others'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
