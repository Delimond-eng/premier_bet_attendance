@extends('pdf.exports._base')

@php
    $metaLines = [
        'Rapport: Pointages hors station d\'affectation',
        'Periode: ' . ($from ?? '') . ' -> ' . ($to ?? ''),
        'Lignes: ' . count($rows ?? []),
    ];
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            <th style="width: 15%;">DATE</th>
            <th style="width: 25%;">AGENT</th>
            <th style="width: 25%;">STATION ATTENDUE</th>
            <th style="width: 25%;">STATION POINTÉE</th>
            <th style="width: 10%;">HEURE</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($rows ?? []) as $p)
            <tr>
                <td>{{ \Carbon\Carbon::parse($p->date_reference)->format('d/m/Y') }}</td>
                <td>
                    <div><strong>{{ $p->agent->fullname ?? '-' }}</strong></div>
                    <div class="muted">{{ $p->agent->matricule ?? '' }}</div>
                </td>
                <td>{{ $p->expected_station->name ?? 'N/A' }}</td>
                <td>{{ $p->stationCheckIn->name ?? 'Inconnue' }}</td>
                <td>{{ $p->started_at ? \Carbon\Carbon::parse($p->started_at)->format('H:i') : '--:--' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
