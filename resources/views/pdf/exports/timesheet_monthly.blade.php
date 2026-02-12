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
            <th style="width: 28%;">Station</th>
            <th style="width: 10%;">Agents</th>
            <th style="width: 10%;">Present</th>
            <th style="width: 10%;">Retard</th>
            <th style="width: 10%;">Absent</th>
            <th style="width: 12%;">Conge</th>
            <th style="width: 20%;">Autorisation</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($rows ?? []) as $r)
            <tr>
                <td><strong>{{ $r['station'] ?? '-' }}</strong></td>
                <td>{{ $r['agents'] ?? 0 }}</td>
                <td>{{ $r['present'] ?? 0 }}</td>
                <td>{{ $r['retard'] ?? 0 }}</td>
                <td>{{ $r['absent'] ?? 0 }}</td>
                <td>{{ $r['conge'] ?? 0 }}</td>
                <td>{{ $r['autorisation'] ?? 0 }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

