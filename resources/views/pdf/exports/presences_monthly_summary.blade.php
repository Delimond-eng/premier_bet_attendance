@extends('pdf.exports._base')

@php
    $metaLines = [
        'Mois: ' . sprintf('%02d', (int) ($month ?? 0)) . '/' . (string) ($year ?? ''),
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Lignes: ' . (is_array($rows ?? null) ? count($rows) : 0),
    ];
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            <th style="width: 18%;">Agent</th>
            <th style="width: 10%;">Station</th>
            <th style="width: 6%;">Près.</th>
            <th style="width: 6%;">Ret.</th>
            <th style="width: 6%;">Abs.</th>
            <th style="width: 6%;">Congé</th>
            <th style="width: 8%;">Auto.</th>
            <th style="width: 8%;">Just. R.</th>
            <th style="width: 8%;">Just. A.</th>
            <th style="width: 8%;">H. Norm.</th>
            <th style="width: 8%;">H. Sup</th>
            <th style="width: 8%;">Total Pr.</th>
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
                <td style="font-size: 8px;">{{ $a['station_name'] ?? '-' }}</td>
                <td>{{ $r['present'] ?? 0 }}</td>
                <td>{{ $r['retard'] ?? 0 }}</td>
                <td>{{ $r['absent'] ?? 0 }}</td>
                <td>{{ $r['conge'] ?? 0 }}</td>
                <td>{{ $r['autorisation'] ?? 0 }}</td>
                <td>{{ $r['retard_justifie'] ?? 0 }}</td>
                <td>{{ $r['absence_justifiee'] ?? 0 }}</td>
                <td class="text-center"><strong>{{ $r['normal_hours_display'] ?? '0h' }}</strong></td>
                <td class="text-center"><strong>{{ $r['overtime_display'] ?? '0h' }}</strong></td>
                <td>{{ $r['total_preste'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
