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
            <th style="width: 40%;">Designation</th>
            <th style="width: 20%;">Station</th>
            <th style="width: 12%;">Heure debut</th>
            <th style="width: 12%;">Heure fin</th>
            <th style="width: 16%;">Tolerance (min)</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($rows ?? []) as $h)
            <tr>
                <td><strong>{{ $h->libelle ?? '-' }}</strong></td>
                <td>{{ optional(($stationsById ?? collect())->get((int) ($h->site_id ?? 0)))->name ?? '' }}</td>
                <td>{{ $h->started_at ?? '' }}</td>
                <td>{{ $h->ended_at ?? '' }}</td>
                <td>{{ (int) ($h->tolerence_minutes ?? 0) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
