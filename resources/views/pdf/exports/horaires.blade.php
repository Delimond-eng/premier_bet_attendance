@extends('pdf.exports._base')

@php
    $metaLines = [
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Lignes: ' . (is_iterable($rows ?? null) ? count($rows) : 0),
    ];
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            <th style="width: 36%;">Designation</th>
            <th style="width: 18%;">Station</th>
            <th style="width: 12%;">Heure debut</th>
            <th style="width: 12%;">Controle</th>
            <th style="width: 12%;">Heure fin</th>
            <th style="width: 10%;">Tolerance (min)</th>
        </tr>
        </thead>
        <tbody>
        @php
            $groups = $grouped ?? null;
        @endphp
        @if(is_iterable($groups))
            @foreach($groups as $g)
                <tr>
                    <td colspan="6"><strong>{{ $g['station_name'] ?? 'Station' }}</strong></td>
                </tr>
                @foreach(($g['rows'] ?? []) as $h)
                    <tr>
                        <td><strong>{{ $h->libelle ?? '-' }}</strong></td>
                        <td>{{ optional(($stationsById ?? collect())->get((int) ($h->site_id ?? 0)))->name ?? '' }}</td>
                        <td>{{ $h->started_at ?? '' }}</td>
                        <td>{{ $h->mid_check ?? '' }}</td>
                        <td>{{ $h->ended_at ?? '' }}</td>
                        <td>{{ (int) ($h->tolerence_minutes ?? 0) }}</td>
                    </tr>
                @endforeach
            @endforeach
        @else
            @foreach(($rows ?? []) as $h)
                <tr>
                    <td><strong>{{ $h->libelle ?? '-' }}</strong></td>
                    <td>{{ optional(($stationsById ?? collect())->get((int) ($h->site_id ?? 0)))->name ?? '' }}</td>
                    <td>{{ $h->started_at ?? '' }}</td>
                    <td>{{ $h->mid_check ?? '' }}</td>
                    <td>{{ $h->ended_at ?? '' }}</td>
                    <td>{{ (int) ($h->tolerence_minutes ?? 0) }}</td>
                </tr>
            @endforeach
        @endif
        </tbody>
    </table>
@endsection
