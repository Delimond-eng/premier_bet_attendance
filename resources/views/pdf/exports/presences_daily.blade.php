@extends('pdf.exports._base')

@php
    $metaLines = [
        'Date: ' . ($date ?? ''),
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Stations: ' . (is_array($groups ?? null) ? count($groups) : 0),
    ];
@endphp

@section('body')
    @foreach(($groups ?? []) as $g)
        <div style="margin-top: 10px; margin-bottom: 6px;">
            <span class="badge badge-ok">{{ $g['station_name'] ?? 'Sans station' }}</span>
            <span class="muted">({{ is_array(($g['rows'] ?? null)) ? count($g['rows']) : 0 }} lignes)</span>
        </div>

        <table>
            <thead>
            <tr>
                <th style="width: 16%;">Agent</th>
                <th style="width: 14%;">Affectation</th>
                <th style="width: 12%;">Check-in</th>
                <th style="width: 12%;">Check-out</th>
                <th style="width: 8%;">Heure entree</th>
                <th style="width: 8%;">Heure sortie</th>
                <th style="width: 8%;">Duree</th>
                <th style="width: 8%;">Retard</th>
                <th style="width: 14%;">Station agent</th>
            </tr>
            </thead>
            <tbody>
            @foreach(($g['rows'] ?? []) as $p)
                <tr>
                    <td>
                        <div><strong>{{ $p->agent?->fullname ?? '-' }}</strong></div>
                        <div class="muted">{{ $p->agent?->matricule ?? '' }}</div>
                    </td>
                    <td>{{ $p->assignedStation?->name ?? '' }}</td>
                    <td>{{ $p->stationCheckIn?->name ?? '' }}</td>
                    <td>{{ $p->stationCheckOut?->name ?? '' }}</td>
                    <td>{{ $p->started_at ? \Carbon\Carbon::parse($p->started_at)->format('H:i') : '' }}</td>
                    <td>{{ $p->ended_at ? \Carbon\Carbon::parse($p->ended_at)->format('H:i') : '' }}</td>
                    <td>{{ $p->duree ?? '' }}</td>
                    <td>
                        @if(($p->retard ?? '') === 'oui')
                            <span class="badge badge-no">Oui</span>
                        @else
                            <span class="badge badge-ok">Non</span>
                        @endif
                    </td>
                    <td>{{ $p->agent?->station?->name ?? '' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach
@endsection

