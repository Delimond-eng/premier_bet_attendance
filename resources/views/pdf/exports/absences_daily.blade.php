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
            <th style="width: 10%;">Date</th>
            <th style="width: 18%;">Agent</th>
            <th style="width: 16%;">Station</th>
            <th style="width: 14%;">Groupe</th>
            <th style="width: 14%;">Horaire</th>
            <th style="width: 10%;">Heure attendue</th>
            <th style="width: 18%;">Justificatif</th>
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
                <td>{{ $a['expected_time'] ?? '--:--' }}</td>
                <td>{{ $r['justificatif'] ?? 'aucun' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

