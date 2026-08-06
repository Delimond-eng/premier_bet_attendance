@extends('pdf.exports._base')

@php
    $isRange = !empty($from) && !empty($to);
    $metaLines = [
        'Période: ' . ($isRange ? ($from . ' au ' . $to) : (sprintf('%02d', (int) ($month ?? 0)) . '/' . (string) ($year ?? ''))),
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Lignes: ' . (is_array($rows ?? null) ? count($rows) : 0),
    ];
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            <th style="width: 15%;">Agent</th>
            <th style="width: 12%;">Fonction</th>
            <th style="width: 10%;">Station</th>
            <th style="width: 5%;">Près.</th>
            <th style="width: 5%;">Ret.</th>
            <th style="width: 5%;">Abs.</th>
            <th style="width: 5%;">AN</th>
            <th style="width: 5%;">Congé</th>
            <th style="width: 5%;">T.AS</th>
            <th style="width: 4%;">T.CM</th>
            <th style="width: 4%;">T.M</th>
            <th style="width: 4%;">T.CC</th>
            <th style="width: 4%;">T.CA</th>
            <th style="width: 6%;">T.C Autres</th>
            <th style="width: 7%;">Just. R.</th>
            <th style="width: 7%;">Just. A.</th>
            <th style="width: 8%;">Ret. Cumul.</th>
            <th style="width: 7%;">H. Norm.</th>
            <th style="width: 7%;">H. Sup</th>
            <th style="width: 7%;">Total Pr.</th>
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
                <td>{{ $a['fonction'] ?? '-' }}</td>
                <td style="font-size: 8px;">{{ $a['station_name'] ?? '-' }}</td>
                <td>{{ $r['present'] ?? 0 }}</td>
                <td>{{ $r['retard'] ?? 0 }}</td>
                <td>{{ $r['absent'] ?? 0 }}</td>
                <td style="color: #991b1b;"><strong>{{ $r['an'] ?? 0 }}</strong></td>
                <td>{{ $r['conge'] ?? 0 }}</td>
                <td>{{ $r['autorisation'] ?? 0 }}</td>
                <td>{{ $r['total_cm'] ?? 0 }}</td>
                <td>{{ $r['total_m'] ?? 0 }}</td>
                <td>{{ $r['total_cc'] ?? 0 }}</td>
                <td>{{ $r['total_ca'] ?? 0 }}</td>
                <td>{{ $r['total_other_leave_types'] ?? 0 }}</td>
                <td>{{ $r['retard_justifie'] ?? 0 }}</td>
                <td>{{ $r['absence_justifiee'] ?? 0 }}</td>
                <td class="text-center" style="color: #991b1b;"><strong>{{ $r['late_display'] ?? '0h' }}</strong></td>
                <td class="text-center"><strong>{{ $r['normal_hours_display'] ?? '0h' }}</strong></td>
                <td class="text-center"><strong>{{ $r['overtime_display'] ?? '0h' }}</strong></td>
                <td>{{ $r['total_preste'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
