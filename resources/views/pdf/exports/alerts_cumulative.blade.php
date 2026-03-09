@extends('pdf.exports._base')

@php
    $metaLines = [
        'Type: ' . ($typeLabel ?? 'Alertes'),
        'Periode: ' . ($from ?? '') . ' -> ' . ($to ?? ''),
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Seuil: >= ' . (int) ($threshold ?? 3),
        'Lignes: ' . (is_array($rows ?? null) ? count($rows) : 0),
    ];
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            <th style="width: 14%;">Mois</th>
            <th style="width: 20%;">Agent</th>
            <th style="width: 14%;">Station</th>
            <th style="width: 12%;">Groupe</th>
            <th style="width: 10%;">Cumul</th>
            <th style="width: 10%;">Seuil</th>
            <th style="width: 20%;">Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($rows ?? []) as $r)
            @php($a = $r['agent'] ?? [])
            <tr>
                <td>{{ $r['month_label'] ?? '' }}</td>
                <td>
                    <div><strong>{{ $a['fullname'] ?? '-' }}</strong></div>
                    <div class="muted">{{ $a['matricule'] ?? '' }}</div>
                </td>
                <td>{{ $a['station_name'] ?? '-' }}</td>
                <td>{{ $a['group_name'] ?? '-' }}</td>
                <td>{{ $r['count'] ?? 0 }}</td>
                <td>{{ $r['threshold'] ?? 0 }}</td>
                <td>{{ $r['action_label'] ?? "Lettre d'explication requise" }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection
