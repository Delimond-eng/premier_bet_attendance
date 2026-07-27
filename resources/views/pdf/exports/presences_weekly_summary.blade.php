@extends('pdf.exports._base')

@php
    $metaLines = [
        'Semaine: ' . ($from ?? '') . ' -> ' . ($to ?? ''),
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Lignes: ' . (is_array($rows ?? null) ? count($rows) : 0),
    ];
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            <th style="width: 18%;">Agent</th>
            <th style="width: 12%;">Station</th>
            <th style="width: 6%;">Près.</th>
            <th style="width: 6%;">Ret.</th>
            <th style="width: 6%;">Abs.</th>
            <th style="width: 6%;">AN</th>
            <th style="width: 7%;">Congé</th>
            <th style="width: 8%;">Auto.</th>
            <th style="width: 8%;">Just. R.</th>
            <th style="width: 8%;">Just. A.</th>
            <th style="width: 8%;">H. Sup</th>
            <th style="width: 7%;">Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($rows ?? []) as $r)
            @php($a = $r['agent'] ?? [])
            <tr>
                <td>
                    <div><strong>{{ $a['fullname'] ?? '-' }}</strong></div>
                    <div class="muted">{{ $a['matricule'] ?? '' }}</div>
                </td>
                <td>{{ $a['station_name'] ?? '-' }}</td>
                <td>{{ $r['present'] ?? 0 }}</td>
                <td>{{ $r['retard'] ?? 0 }}</td>
                <td>{{ $r['absent'] ?? 0 }}</td>
                <td style="color: #991b1b;"><strong>{{ $r['an'] ?? 0 }}</strong></td>
                <td>{{ $r['conge'] ?? 0 }}</td>
                <td>{{ $r['autorisation'] ?? 0 }}</td>
                <td>{{ $r['retard_justifie'] ?? 0 }}</td>
                <td>{{ $r['absence_justifiee'] ?? 0 }}</td>
                <td>{{ $r['overtime_display'] ?? '0h' }}</td>
                <td>{{ $r['total_preste'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
