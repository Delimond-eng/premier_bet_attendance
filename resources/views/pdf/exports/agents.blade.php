@extends('pdf.exports._base')

@php
    $metaLines = [
        'Station: ' . (($station->name ?? null) ?: 'Toutes'),
        'Lignes: ' . (isset($rows) ? $rows->count() : 0),
    ];
@endphp

@section('body')
    <table>
        <thead>
        <tr>
            <th style="width: 18%;">Matricule</th>
            <th style="width: 32%;">Nom complet</th>
            <th style="width: 25%;">Station</th>
            <th style="width: 12%;">Statut</th>
            <th style="width: 13%;">Cree le</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($rows ?? []) as $a)
            <tr>
                <td>{{ $a->matricule ?? '' }}</td>
                <td><strong>{{ $a->fullname ?? '-' }}</strong></td>
                <td>{{ $a->station?->name ?? '-' }}</td>
                <td>{{ $a->status ?? '-' }}</td>
                <td>{{ $a->created_at ? \Carbon\Carbon::parse($a->created_at)->format('Y-m-d H:i') : '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endsection

