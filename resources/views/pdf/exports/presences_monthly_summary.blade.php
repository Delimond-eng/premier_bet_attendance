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
            <th style="width: 22%;">Agent</th>
            <th style="width: 14%;">Station</th>
            <th style="width: 7%;">Present</th>
            <th style="width: 7%;">Retard</th>
            <th style="width: 7%;">Absent</th>
            <th style="width: 7%;">Conge</th>
            <th style="width: 10%;">Autorisation</th>
            <th style="width: 10%;">Justif retard</th>
            <th style="width: 10%;">Justif absence</th>
            <th style="width: 6%;">Total</th>
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
                <td>{{ $r['conge'] ?? 0 }}</td>
                <td>{{ $r['autorisation'] ?? 0 }}</td>
                <td>{{ $r['retard_justifie'] ?? 0 }}</td>
                <td>{{ $r['absence_justifiee'] ?? 0 }}</td>
                <td>{{ $r['total_preste'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

