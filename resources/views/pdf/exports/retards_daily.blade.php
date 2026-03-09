@extends('pdf.exports._base')

@php
    $metaLines = [
        'Periode: ' . ($from ?? '') . ' -> ' . ($to ?? ''),
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Lignes: ' . (is_array($rows ?? null) ? count($rows) : 0),
    ];
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            <th style="width: 9%;">Date</th>
            <th style="width: 16%;">Agent</th>
            <th style="width: 14%;">Station</th>
            <th style="width: 12%;">Groupe</th>
            <th style="width: 12%;">Horaire</th>
            <th style="width: 9%;">Heure prevue</th>
            <th style="width: 9%;">Heure arrivee</th>
            <th style="width: 8%;">Retard (min)</th>
            <th style="width: 11%;">Justificatif</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($rows ?? []) as $r)
            @php($a = $r['agent'] ?? [])
            <tr>
                <td>{{ $r['date'] ?? '' }}</td>
                <td>
                    <div><strong>{{ $a['fullname'] ?? '-' }}</strong></div>
                    <div class="muted">{{ $a['matricule'] ?? '' }}</div>
                </td>
                <td>{{ $a['station_name'] ?? '-' }}</td>
                <td>{{ $a['group_name'] ?? '-' }}</td>
                <td>{{ $a['schedule_label'] ?? '-' }}</td>
                <td>{{ $r['expected_time'] ?? '--:--' }}</td>
                <td>{{ $r['arrival_time'] ?? '--:--' }}</td>
                <td>{{ $r['late_minutes'] ?? 0 }}</td>
                <td>{{ $r['justificatif'] ?? 'non justifie' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
