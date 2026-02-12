@extends('pdf.exports._base')

@php
    $metaLines = [
        'Date: ' . ($date ?? ''),
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Lignes: ' . (isset($rows) ? $rows->count() : 0),
    ];
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            <th style="width: 18%;">Agent</th>
            <th style="width: 14%;">Station affectation</th>
            <th style="width: 12%;">Check-in</th>
            <th style="width: 12%;">Check-out</th>
            <th style="width: 10%;">Date</th>
            <th style="width: 8%;">Entree</th>
            <th style="width: 8%;">Sortie</th>
            <th style="width: 10%;">Duree</th>
            <th style="width: 8%;">Retard</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($rows ?? []) as $p)
            <tr>
                <td>
                    <div><strong>{{ $p->agent?->fullname ?? '-' }}</strong></div>
                    <div class="muted">{{ $p->agent?->matricule ?? '' }}</div>
                </td>
                <td>{{ $p->assignedStation?->name ?? '-' }}</td>
                <td>{{ $p->stationCheckIn?->name ?? '-' }}</td>
                <td>{{ $p->stationCheckOut?->name ?? '-' }}</td>
                <td>{{ \Carbon\Carbon::parse($p->date_reference)->toDateString() }}</td>
                <td>{{ $p->started_at ? \Carbon\Carbon::parse($p->started_at)->format('H:i') : '--:--' }}</td>
                <td>{{ $p->ended_at ? \Carbon\Carbon::parse($p->ended_at)->format('H:i') : '--:--' }}</td>
                <td>{{ $p->duree ?? '--' }}</td>
                <td>
                    @if(($p->retard ?? '') === 'oui')
                        <span class="badge badge-no">Oui</span>
                    @else
                        <span class="badge badge-ok">Non</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

