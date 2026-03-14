@extends('pdf.exports._base')

@php
    $metaLines = [
        'Date: ' . ($date ?? ''),
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Stations groupées: ' . (is_array($groups ?? null) ? count($groups) : 0),
    ];
@endphp

@section('body')
    @foreach(($groups ?? []) as $g)
        <div style="margin-top: 15px; margin-bottom: 8px;">
            <span class="badge badge-info" style="font-size: 11px; padding: 4px 10px;">{{ $g['station_name'] ?? 'Sans station' }}</span>
            <span class="text-muted">({{ count($g['rows'] ?? []) }} pointages enregistrés)</span>
        </div>

        <table>
            <thead>
            <tr>
                <th style="width: 14%;">Agent</th>
                <th style="width: 10%;">Check-in / Out</th>
                <th style="width: 6%;">Entrée</th>
                <th style="width: 6%;">Sortie</th>
                <th style="width: 8%;">H. Normales</th>
                <th style="width: 8%;">Heures Sup</th>
                <th style="width: 8%;">Durée Tot.</th>
                <th style="width: 7%;">Retard</th>
                <th style="width: 15%;">Motifs / Justificatifs</th>
                <th style="width: 10%;">Affectation</th>
            </tr>
            </thead>
            <tbody>
            @foreach(($g['rows'] ?? []) as $p)
                <tr>
                    <td>
                        <div class="text-bold">{{ $p->agent?->fullname ?? '-' }}</div>
                        <div class="text-muted">{{ $p->agent?->matricule ?? '' }}</div>
                    </td>
                    <td class="text-muted" style="font-size: 8px;">
                        IN: {{ $p->stationCheckIn?->name ?? '-' }}<br>
                        OUT: {{ $p->stationCheckOut?->name ?? '-' }}
                    </td>
                    <td class="text-center">{{ $p->started_at ? \Carbon\Carbon::parse($p->started_at)->format('H:i') : '--:--' }}</td>
                    <td class="text-center">{{ $p->ended_at ? \Carbon\Carbon::parse($p->ended_at)->format('H:i') : '--:--' }}</td>
                    <td class="text-center"><span class="badge badge-ok">{{ $p->normal_hours_display ?? '0h' }}</span></td>
                    <td class="text-center"><span class="badge badge-warn">{{ $p->overtime_display ?? '0h' }}</span></td>
                    <td class="text-center text-bold">{{ $p->duree ?? '--' }}</td>
                    <td class="text-center">
                        @if(($p->retard ?? '') === 'oui')
                            <span class="badge badge-no">OUI</span>
                        @else
                            <span class="badge badge-ok">NON</span>
                        @endif
                    </td>
                    <td style="font-size: 8px;">{{ $p->motif ?? '-' }}</td>
                    <td class="text-center">{{ $p->assignedStation?->name ?? '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach
@endsection
