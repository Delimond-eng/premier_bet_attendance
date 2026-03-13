@extends('pdf.exports._base')

@php
    $metaLines = [
        'Date de référence: ' . ($date ?? ''),
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Nombre de pointages: ' . (isset($rows) ? $rows->count() : 0),
    ];
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            <th style="width: 15%;">Agent</th>
            <th style="width: 12%;">Affectation</th>
            <th style="width: 10%;">Check-in</th>
            <th style="width: 10%;">Check-out</th>
            <th style="width: 6%;">Entrée</th>
            <th style="width: 6%;">Sortie</th>
            <th style="width: 8%;">H. Normales</th>
            <th style="width: 8%;">Heures Sup</th>
            <th style="width: 8%;">Durée Tot.</th>
            <th style="width: 10%;">Contrôle</th>
            <th style="width: 7%;">Retard</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($rows ?? []) as $p)
            <tr>
                <td>
                    <div class="text-bold">{{ $p->agent?->fullname ?? '-' }}</div>
                    <div class="text-muted">{{ $p->agent?->matricule ?? '' }}</div>
                </td>
                <td>{{ $p->assignedStation?->name ?? '-' }}</td>
                <td>{{ $p->stationCheckIn?->name ?? '-' }}</td>
                <td>{{ $p->stationCheckOut?->name ?? '-' }}</td>
                <td class="text-center">{{ $p->started_at ? \Carbon\Carbon::parse($p->started_at)->format('H:i') : '--:--' }}</td>
                <td class="text-center">{{ $p->ended_at ? \Carbon\Carbon::parse($p->ended_at)->format('H:i') : '--:--' }}</td>
                <td class="text-center"><span class="badge badge-info">{{ $p->normal_hours_display ?? '0h' }}</span></td>
                <td class="text-center"><span class="badge badge-warn">{{ $p->overtime_display ?? '0h' }}</span></td>
                <td class="text-center text-bold">{{ $p->duree ?? '--' }}</td>
                <td class="text-center">{{ $p->mid_check ? \Carbon\Carbon::parse($p->mid_check)->format('H:i') : '--:--' }}</td>
                <td class="text-center">
                    @if(($p->retard ?? '') === 'oui')
                        <span class="badge badge-no">OUI</span>
                    @else
                        <span class="badge badge-ok">NON</span>
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
